<?php

declare(strict_types=1);

namespace ImranDev\UniversalSlug\Commands;

use Illuminate\Console\Command;
use ImranDev\UniversalSlug\UniversalSlug;

class GenerateSlugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slug:generate
                            {text : The text or title string to convert to a slug}
                            {--separator=- : The separator character to use}
                            {--lang= : The language code for specific dictionary replacements}
                            {--words= : Limit slug to the first N words}
                            {--suffix= : Add random alphanumeric suffix length (e.g. 5)}
                            {--mode=preserve_unicode : Mode (preserve_unicode, transliterate, ascii_only)}
                            {--no-stopwords : Strip common filler words/stopwords}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a universal multilingual URL slug from the terminal';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $text = (string) $this->argument('text');
        $separator = (string) $this->option('separator');
        $lang = $this->option('lang') ? (string) $this->option('lang') : null;
        $words = $this->option('words') ? (int) $this->option('words') : null;
        $suffix = $this->option('suffix') ? (int) $this->option('suffix') : false;
        $mode = (string) $this->option('mode');
        $removeStopwords = (bool) $this->option('no-stopwords');

        $options = [
            'mode' => $mode,
            'word_limit' => $words,
            'random_suffix' => $suffix,
            'remove_stopwords' => $removeStopwords,
        ];

        $slug = UniversalSlug::generate($text, $separator, $lang, $options);

        $this->info("Generated Slug: {$slug}");

        return self::SUCCESS;
    }
}
