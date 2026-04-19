<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Doctor;
use App\Models\MedicalRep;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class AppServiceProvider extends ServiceProvider
{
    private function shouldRegisterL5SwaggerDocblockAnalyser(): bool
    {
        if (! $this->app->runningInConsole()) {
            return true;
        }

        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if ($arg === 'config:cache') {
                return false;
            }
        }

        return true;
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'doctor' => Doctor::class,
            'medical_rep' => MedicalRep::class,
            'company' => Company::class,
            'rep' => MedicalRep::class,
        ]);

        $this->app->booted(function () {
            $this->app['migrator']->path(database_path('migrations'.DIRECTORY_SEPARATOR.'E_REP'));

            // L5-Swagger defaults to attributes-only; enable @OA docblocks (requires doctrine/annotations).
            // Skip while `config:cache` runs or the cached config cannot be serialized.
            if ($this->shouldRegisterL5SwaggerDocblockAnalyser()) {
                config()->set('l5-swagger.defaults.scanOptions.analyser', new ReflectionAnalyser([
                    new AttributeAnnotationFactory(),
                    new DocBlockAnnotationFactory(),
                ]));
            }
        });
    }
}
