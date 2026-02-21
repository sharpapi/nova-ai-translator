# Changelog

All notable changes to `nova-ai-translator` will be documented in this file

## v1.0.4 - 2026-02-21

Security: bumped minimum Laravel version to ^10.48.29 to address file validation bypass vulnerability (CVE). Dropped Laravel 9 support (EOL since Feb 2024).

## v1.0.3 - 2026-02-21

Migrated from deprecated monolithic `sharpapi/sharpapi-laravel-client` to focused `sharpapi/laravel-content-translate` package. Bumped minimum PHP version to 8.1. Added local `SharpApiVoiceTone` enum (no longer depends on the old client for it).

## v1.0.2 - 2025-04-08

Better handling new versions of Nova 4 & 5

## v1.0.1 - 2025-03-04

Small fixes

## v1.0.0 - 2024-11-10

- initial release

## 1.0.0 - 2018-XX-XX

- initial release
