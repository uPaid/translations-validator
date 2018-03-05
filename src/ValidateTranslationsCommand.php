<?php

namespace Upaid\TranslationsValidator;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;

class ValidateTranslationsCommand extends Command
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations-validator:validate 
        {path? : An absolute path to lang directory, a standard one is used if empty (base_path("resources/lang"))} 
        {--throw-parse-exception : Whether ParseException should be thrown or not}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if all translation YAML files are correct. Duplicated keys are treated as errors!';

    /**
     * @var StrictYamlParser
    */
    protected $yamlParser;

    /**
     * @var \Illuminate\Filesystem\Filesystem
    */
    protected $filesystem;

    /**
     * @var array
    */
    protected $errors = [];

    /**
     * Create a new command instance.
     */
    public function __construct(StrictYamlParser $parser, Filesystem $filesystem)
    {
        parent::__construct();

        $this->yamlParser = $parser;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->argument('path') ?: resource_path('lang');
        $this->readLanguagesFiles($path)->each(function (SplFileInfo $fileInfo) {
            $this->checkFile($fileInfo);
        });

        $count = count($this->errors);
        if ($count) {
            $message = 'There are some errors in translation files. You shall not pass!!!';
            if ($this->option('throw-parse-exception')) {
                $this->error(implode(PHP_EOL, $this->errors));
                throw new ParseException($message);
            }

            $this->error($message . PHP_EOL);
            $this->error(implode(PHP_EOL, $this->errors));
        } else {
            $this->info('No errors in translation files. You are good to go :)');
        }

        return $count;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /******************************************************************************************************************/

    /**
     * read all language files from a directory specified in $path argument
     *
     * @return Collection of SplFileInfo objects
    */
    protected function readLanguagesFiles(string $path) : Collection
    {
        return Collection::make($this->filesystem->allFiles($path))
            ->filter(function (SplFileInfo $fileInfo) {
                return in_array($fileInfo->getExtension(), ['yml', 'yaml']);
            });
    }

    /**
     * try to parse language file and write errors to $this->errors
    */
    protected function checkFile(SplFileInfo $fileInfo) : void
    {
        $currentFile = '';
        try {
            $currentFile = $fileInfo->getPathname();
            $content = $this->convertToUtf8($this->filesystem->get($currentFile));
            $this->yamlParser->parse($content);
        } catch (ParseException $e) {
            $e->setParsedFile($currentFile);
            $this->errors[] = $e->getMessage();
        }
    }

    protected function convertToUtf8(string $string) : string
    {
        return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string, 'UTF-8, ISO-8859-1', true));
    }
}
