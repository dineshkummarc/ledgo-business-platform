<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Exceptions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SlugRedirectException extends RuntimeException
{
    /**
     * Create a new slug redirect exception.
     */
    public function __construct(
        public readonly Model $model,
        public readonly string $oldSlug,
        public readonly string $field = 'slug',
        public readonly int $status = 301,
    ) {
        parent::__construct('The requested slug has been permanently moved.');
    }

    /**
     * Convert the exception into a 301 (or configured) redirect response.
     */
    public function render(Request $request): Response
    {
        $target = $this->buildTargetUrl($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'The slug has changed.',
                'redirect' => $target,
            ], $this->status)->header('Location', $target);
        }

        return redirect()->to($target, $this->status);
    }

    /**
     * Replace the stale slug segment in the current URL.
     */
    protected function buildTargetUrl(Request $request): string
    {
        $newSlug = (string) $this->model->getAttribute($this->field);
        $path = $request->getPathInfo();
        $replaced = preg_replace(
            '/'.preg_quote($this->oldSlug, '/').'(?=\/|$)/u',
            $newSlug,
            $path,
            1
        );

        if (! is_string($replaced) || $replaced === '' || $replaced === $path) {
            $replaced = '/'.$newSlug;
        }

        $target = $request->getSchemeAndHttpHost().$replaced;

        if ($query = $request->getQueryString()) {
            $target .= '?'.$query;
        }

        return $target;
    }
}
