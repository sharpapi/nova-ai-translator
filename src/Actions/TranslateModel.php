<?php

namespace SharpAPI\NovaAiTranslator\Actions;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\ActionResponse;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Trix;
use Laravel\Nova\Http\Requests\NovaRequest;
use SharpAPI\Core\Exceptions\ApiException;
use SharpAPI\SharpApiService\Enums\SharpApiVoiceTone;
use SharpAPI\SharpApiService\SharpApiService;
use Spatie\Translatable\HasTranslations;

/**
 * @api
 */
class TranslateModel extends Action implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $name = '🤖 Initiate AI Translation';

    /**
     * Handle the translation action
     *
     * @throws GuzzleException
     * @throws ApiException
     */
    public function handle(ActionFields $fields, $models): ActionResponse|Action
    {
        set_time_limit(600);
        // Verify that the locales configuration exists
        $localesConfig = config('app.locales');

        if (! $localesConfig || ! is_array($localesConfig)) {
            $this->fail("The language configuration is missing in 'config/app.php'.".
                "Please define 'locales' with supported languages.");
        }

        // Check if the SharpAPI client API key is set
        $apiKey = config('sharpapi-client.api_key');
        if (empty($apiKey)) {
            $this->fail('The SharpAPI client API key is not configured. '.
                "Please set 'SHARP_API_KEY' in '.env'.");
        }

        $sourceLang = $fields->get('source_lang');
        $targetLang = $fields->get('target_lang');
        $tone = $fields->get('tone') ?? SharpApiVoiceTone::NEUTRAL->value;

        // Check if source and target languages are in the locales configuration
        if (! array_key_exists($sourceLang, $localesConfig) || ! array_key_exists($targetLang, $localesConfig)) {
            return Action::danger(
                'The selected languages are not supported.'.
                "Please ensure both source and target languages are defined in the 'locales' configuration."
            );
        }

        $fieldsAlreadyTranslated = [];

        foreach ($models as $model) {
            if (in_array(HasTranslations::class, class_uses($model))) {
                $translatableFields = $model->getTranslatableAttributes();
                $hasExistingContent = $this->checkExistingTranslations($model, $translatableFields, $targetLang);

                if ($hasExistingContent) {
                    return Action::danger(
                        'All the target language fields already contain content.'.
                        'Clear them and rerun the action if you wish to overwrite.');
                }

                // Proceed with translation if not all fields have content
                $this->translateModelFields(
                    $model,
                    $translatableFields,
                    $sourceLang,
                    $targetLang,
                    $tone,
                    $fieldsAlreadyTranslated
                );
                $this->markAsFinished($model);
            }
        }

        $translatedFields = implode(', ', $fieldsAlreadyTranslated);

        return Action::message("Translation completed successfully for fields: $translatedFields.");
    }

    /**
     * Translate model fields
     *
     * @throws GuzzleException|ApiException
     */
    private function translateModelFields(
        $model,
        array $translatableFields,
        string $sourceLang,
        string $targetLang,
        string $tone,
        array &$fieldsAlreadyTranslated
    ): void {
        foreach ($translatableFields as $field) {
            $currentTranslation = $model->getTranslation($field, $targetLang, false);
            $sourceTranslation = $model->getTranslation($field, $sourceLang, false);

            if (empty(trim($currentTranslation)) && ! empty(trim($sourceTranslation))) {
                $translatedText = $this->translateText($sourceTranslation, $sourceLang, $targetLang, $tone);
                $model->setTranslation($field, $targetLang, $translatedText);
                $fieldsAlreadyTranslated[] = $field;
            }
        }

        $model->save();
    }

    /**
     * Perform the translation by calling SharpApiService
     *
     * @throws GuzzleException|ApiException
     */
    private function translateText(
        string $text,
        string $sourceLang,
        string $targetLang,
        string $tone = 'neutral'
    ): string {
        $fromLanguage = config('app.locales')[$sourceLang];
        $toLanguage = config('app.locales')[$targetLang];
        $sharpApiService = new SharpApiService;

        $jobUrl = $sharpApiService->translate(
            $text,
            $toLanguage,
            $tone,
            'Source language is '.$fromLanguage
        );
        $jobResults = $sharpApiService->fetchResults($jobUrl);

        return $jobResults->getResultObject()->content ?? ''; // Return translated text or empty string if not present
    }

    /**
     * Check if all translatable fields in the target language already have content.
     */
    private function checkExistingTranslations($model, array $translatableFields, string $targetLang): bool
    {
        foreach ($translatableFields as $field) {

            $currentTranslation = $model->getTranslation($field, $targetLang, false);
            // If any field is empty or whitespace, return false to allow translation
            if (empty(trim($currentTranslation))) {
                Log::debug('checkExistingTranslations: (empty(trim($currentTranslation)))');

                return false;
            }
        }

        // If we complete the loop without finding an empty field, all fields have content
        return true;
    }

    /**
     * Define the fields for the action's form
     */
    public function fields(NovaRequest $request): array
    {
        $locales = config('app.locales');
        $defaultSourceLang = config('app.locale');

        return [
            Trix::make('Translation Information')
                ->readonly()
                ->fullWidth()
                ->dependsOn(
                    ['source_lang', 'target_lang'],
                    function (Trix $field, NovaRequest $request, FormData $formData) use ($locales) {
                        $sourceLang = $formData->source_lang;
                        $targetLang = $formData->target_lang;

                        // Null-safe fallback for batch requests
                        $translatableFields = [];

                        try {
                            $modelQuery = $request->findModelQuery();
                            if ($modelQuery) {
                                $modelClass = $modelQuery->getModel();
                                if ($modelClass && in_array(HasTranslations::class, class_uses($modelClass))) {
                                    $translatableFields = $modelClass->getTranslatableAttributes();
                                }
                            }
                        } catch (\Throwable $e) {
                            // fallback for batch requests with no model context
                            $translatableFields = ['title', 'subtitle', 'content']; // or leave empty if uncertain
                        }

                        if ($sourceLang && $targetLang) {
                            $sourceLangName = $locales[$sourceLang] ?? ucfirst($sourceLang);
                            $targetLangName = $locales[$targetLang] ?? ucfirst($targetLang);
                            $field->value =
                                "Fields to be translated from <b>$sourceLangName</b> to <b>$targetLangName</b>:".
                                "<ul style='padding-left: 20px;'>".
                                implode('', array_map(fn ($f) => "<li>$f</li>", $translatableFields)).
                                '</ul><br />'.
                                "<em>If any field above already contains content in $targetLangName, translation for it will be ignored.</em>";
                        } else {
                            $field->value = 'Select both source and target languages to see the translatable fields.';
                        }
                    }),

            Select::make('Source Language', 'source_lang')
                ->options($locales)
                ->default($defaultSourceLang)
                ->rules('required'),

            Select::make('Target Language', 'target_lang')
                ->options(array_filter($locales, fn ($key) => $key !== $defaultSourceLang, ARRAY_FILTER_USE_KEY))
                ->rules('required')
                ->dependsOn(['source_lang'], function (Select $field, NovaRequest $request, FormData $formData) use ($locales) {
                    $sourceLang = $formData->source_lang;
                    $targetOptions = array_filter(
                        $locales,
                        fn ($key) => $key !== $sourceLang,
                        ARRAY_FILTER_USE_KEY
                    );

                    $field->options($targetOptions);
                }),

            Select::make('Tone', 'tone')
                ->options(
                    collect(SharpApiVoiceTone::cases())
                        ->mapWithKeys(fn ($tone) => [$tone->value => ucfirst($tone->value)])
                        ->toArray()
                )
                ->withMeta(['value' => SharpApiVoiceTone::NEUTRAL->value])
                ->help('[Optional] Choose the voice tone for the translation'),
        ];
    }
}
