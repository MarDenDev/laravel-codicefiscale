<?php

namespace robertogallea\LaravelCodiceFiscale;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleValidationException;

class CodiceFiscaleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(CodiceFiscale $codiceFiscale)
    {
        $this->registerValidator($codiceFiscale);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->publishConfig();

        $this->app->singleton(CodiceFiscale::class, function () {
            $decoder = config('codicefiscale.city-decoder');

            return new CodiceFiscale(
                new $decoder()
            );
        });
    }

    public function registerValidator(CodiceFiscale $codiceFiscale)
    {
        Validator::extend('codice_fiscale', function ($attribute, $value, $parameters, $validator) use ($codiceFiscale) {
            try {
                $codiceFiscale->parse($value);
            } catch (CodiceFiscaleValidationException $exception) {
                switch ($exception->getCode()) {
                    case CodiceFiscaleValidationException::NO_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.no_code'));
                        break;
                    case CodiceFiscaleValidationException::WRONG_SIZE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.wrong_size'));
                        break;
                    case CodiceFiscaleValidationException::BAD_CHARACTERS:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.bad_characters'));
                        break;
                    case CodiceFiscaleValidationException::BAD_OMOCODIA_CHAR:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.bad_omocodia_char'));
                        break;
                    case CodiceFiscaleValidationException::WRONG_CODE:
                        $error_msg = str_replace([':attribute'], [$attribute], trans('validation.codice_fiscale.wrong_code'));
                        break;
                }

                $validator->addReplacer('codice_fiscale', function ($message, $attribute, $rule, $parameters, $validator) use ($error_msg) {
                    return str_replace([':attribute'], [$validator->getDisplayableAttribute($attribute)], str_replace('codice fiscale', ':attribute', $error_msg));
                });

                return false;
            }

            return true;
        });
    }

    private function publishConfig()
    {
        $configPath = $this->packagePath('config/codicefiscale.php');

        $this->publishes([
            $configPath => config_path('codicefiscale.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, 'codicefiscale');
    }

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }
}
