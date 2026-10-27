<?php

declare(strict_types=1);

namespace Essa\APIToolKit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $app = $this->app;

        Builder::macro('dynamicPaginate', function () use ($app) {
            /** @var Request $request */
            $request = $app->get('request');

            if ('none' === $request->input('pagination')) {
                return $this->get();
            }

            $page = Paginator::resolveCurrentPage();

            $default = filter_var(config('api-tool-kit.default_pagination_number', 20), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 20;
            $raw = $request->input('per_page', $default);
            $perPage = (is_string($raw) || is_int($raw) ? filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false) ?: $default;
            $maxPerPage = filter_var(config('api-tool-kit.max_pagination_limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($maxPerPage !== false) {
                $perPage = min($perPage, $maxPerPage);
            }

            $results = ($total = $this->toBase()->getCountForPagination())
                ? $this->forPage($page, $perPage)->get(['*'])
                : $this->model->newCollection();

            return $this->paginator($results, $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ])->withQueryString();
        });
    }
}
