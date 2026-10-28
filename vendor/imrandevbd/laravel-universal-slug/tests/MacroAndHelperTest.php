<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('supports Str::universalSlug macro', function () {
    $result = Str::universalSlug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা');
    expect($result)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
});

it('supports Stringable fluent macro str()->universalSlug()', function () {
    $result = (string) str('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা')->universalSlug();
    expect($result)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');
});

it('supports universal_slug() global helper with word limit and random suffix', function () {
    $result = universal_slug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা');
    expect($result)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');

    $resultWithLimit = universal_slug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা ও তার কারণ', word_limit: 7);
    expect($resultWithLimit)->toBe('রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা');

    $resultWithRandom = universal_slug('রাবিতে সাড়ে ৬ বছরে ১৬ শিক্ষার্থীর আত্মহত্যা', random_suffix: 5);
    expect($resultWithRandom)->toMatch('/^রাবিতে-সাড়ে-৬-বছরে-১৬-শিক্ষার্থীর-আত্মহত্যা-[a-z0-9]{5}$/u');
});
