<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Cms\CmsPageService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly CmsPageService $pages,
    ) {}

    public function __invoke(string $slug): Response
    {
        $page = $this->pages->findPublished($slug);

        if ($page === null) {
            throw new NotFoundHttpException;
        }

        $component = match ($slug) {
            'about' => 'storefront/about',
            'contact' => 'storefront/contact',
            'privacy' => 'storefront/privacy',
            'terms' => 'storefront/terms',
            'faqs' => 'storefront/faq',
            'wholesale' => 'storefront/wholesale',
            default => throw new NotFoundHttpException,
        };

        return Inertia::render($component, [
            'cmsPage' => $this->pages->publicPayload($page),
        ]);
    }
}
