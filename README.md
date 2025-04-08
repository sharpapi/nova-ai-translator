# SharpAPI AI Translator for Laravel Nova

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sharpapi/nova-ai-translator.svg?style=flat-square)](https://packagist.org/packages/sharpapi/nova-ai-translator)
[![Total Downloads](https://img.shields.io/packagist/dt/sharpapi/nova-ai-translator.svg?style=flat-square)](https://packagist.org/packages/sharpapi/nova-ai-translator)

Welcome to **SharpAPI AI Translator for Laravel Nova** – the package that’s here to make your content translation smoother and smarter with the power of SharpAPI's [Advanced Text Translator AI API](https://sharpapi.com/en/catalog/ai/content-marketing-automation/advanced-text-translator) and to leverage [AI for Laravel](https://SharpAPI.com/). This package extends the already-awesome [Spatie's `laravel-translatable`](https://spatie.be/docs/laravel-translatable/) with a Nova action you can trigger from your resource’s edit or list views.

`🤖 Initiate AI Translation` can be run from either:
- **The Nova resources list view:**
  > <img src="images/form-open-list.png" title="Form Open From the List"  style="max-width: 700px;" />

- **Or from the edit screen of an individual resource:**
  > <img src="images/form-open.png" title="Form Open Menu"  style="max-width: 700px;"/>

Here's the form you’ll use to dispatch the translation:
> <img src="images/form.png" title="Form PopUp"  style="max-width: 500px;" />

## Requirements

- **Laravel**: ^9.0+
- **Laravel Nova**: 4.0+
- **PHP**: 8.0+
- **spatie/laravel-translatable**: used for detecting translatable fields

## Installation & Configuration

### 1. Install the package via Composer

```bash
composer require sharpapi/nova-ai-translator
```

### 2. API Key Configuration

You’ll need an API key from [SharpAPI.com](https://SharpAPI.com/). Add it to your `.env` file like so:

```
SHARP_API_KEY=your-sharp-api-key
```

### 3. Supported Locales Configuration

Add your supported locales in `config/app.php` under the `locales` key:

```php
return [
   'locales' => [
       'en' => 'English',
       'es' => 'Spanish',
       'fr' => 'French',
       // Add other supported languages here
   ],
];
```

### 4. Add to Nova Resource Models

For any model you want to translate, make sure it:
- Uses Spatie’s `HasTranslations` trait.
- Specifies which attributes are `translatable`.
- **[OPTIONAL], yet Highly Recommended**: Use `Actionable` and `Notifiable` traits to track actions and notifications. This ensures you can log and monitor translation progress effectively.

Example:

```php
namespace App;

use Laravel\Nova\Actions\Actionable;
use Illuminate\Notifications\Notifiable;
use Spatie\Translatable\HasTranslations;

class BlogPost
{
   use Actionable, Notifiable, HasTranslations;

   protected $translatable = ['title', 'subtitle', 'content'];
}
```

### 5. Attach the Action in a Nova Resource

Add the `TranslateModel` action to any Nova resource, such as `BlogPost`:

```php
use SharpAPI\NovaAiTranslator\Actions\TranslateModel;

public function actions()
{
   return [
       (new TranslateModel())->enabled(),
   ];
}
```

### 6. Queue Setup

The `TranslateModel` action runs as a queued job, which is essential for smooth, asynchronous processing. Make sure your app’s queue is up and running for best results.

## Using the TranslateModel Action

- From the resource’s edit screen, trigger the `TranslateModel` action.
- A form will pop up where you can select the source and target languages and choose an optional tone.
- **Note**: The action checks if all target language fields already contain content. If they do, you’ll be prompted to clear these fields first if you want a fresh AI-generated translation.
- Once triggered, the action queues the job, and you can monitor its progress if your model uses the `Actionable` and `Notifiable` traits.

### Tips

- **Translation logs**: Helpful for tracking what was translated.
  > <img src="images/actions-log.png" width="750"/>

- **Error handling**: With `Laravel\Nova\Actions\Actionable`, you get detailed logs if something goes awry.
  > <img src="images/actions-log-error.png" width="750"/>

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent updates.

## Credits

- [A2Z WEB LTD](https://github.com/a2zwebltd)
- [Dawid Makowski](https://github.com/makowskid)
- Powered by [SharpAPI](https://sharpapi.com/) – your go-to for leveling up with [AI in Laravel](https://sharpapi.com/en/tag/laravel).

## License

Licensed under the MIT License – see the [License File](LICENSE.md) for details.

## Follow SharpAPI for More!

Stay tuned for updates, tips, and tricks:
- [SharpAPI X (formerly Twitter)](https://x.com/SharpAPI)
- [SharpAPI YouTube](https://www.youtube.com/@SharpAPI)
- [SharpAPI Vimeo](https://vimeo.com/SharpAPI)
- [SharpAPI LinkedIn](https://www.linkedin.com/products/a2z-web-ltd-sharpapicom-automate-with-aipowered-api/)
- [SharpAPI Facebook](https://www.facebook.com/profile.php?id=61554115896974)
