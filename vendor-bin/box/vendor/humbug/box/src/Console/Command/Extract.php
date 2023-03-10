<?php

declare(strict_types=1);

/*
 * This file is part of the box project.
 *
 * (c) Kevin Herrera <kevin@herrera.io>
 *     Théo Fidry <theo.fidry@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace KevinGH\Box\Console\Command;

use function count;
use Fidry\Console\Command\Command;
use Fidry\Console\Command\Configuration;
use Fidry\Console\ExitCode;
use Fidry\Console\Input\IO;
use KevinGH\Box\Box;
use function KevinGH\Box\bump_open_file_descriptor_limit;
use function KevinGH\Box\create_temporary_phar;
use function KevinGH\Box\FileSystem\dump_file;
use function KevinGH\Box\FileSystem\remove;
use function realpath;
use RecursiveIteratorIterator;
use RuntimeException;
use function sprintf;
use function strlen;
use function substr;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

/**
 * @private
 */
final class Extract implements Command
{
    private const PHAR_ARG = 'phar';
    private const OUTPUT_ARG = 'output';

    public function getConfiguration(): Configuration
    {
        return new Configuration(
            'extract',
            '🚚  Extracts a given PHAR into a directory',
            '',
            [
                new InputArgument(
                    self::PHAR_ARG,
                    InputArgument::REQUIRED,
                    'The PHAR file.',
                ),
                new InputArgument(
                    self::OUTPUT_ARG,
                    InputArgument::REQUIRED,
                    'The output directory',
                ),
            ],
        );
    }

    public function execute(IO $io): int
    {
        $filePath = self::getPharFilePath($io);
        $outputDir = $io->getArgument(self::OUTPUT_ARG)->asNonEmptyString();

        if (null === $filePath) {
            return ExitCode::FAILURE;
        }

        [$box, $cleanUpTmpPhar] = $this->getBox($filePath, $io);

        if (null === $box) {
            return ExitCode::FAILURE;
        }

        $restoreLimit = bump_open_file_descriptor_limit(count($box), $io);

        $cleanUp = static function () use ($cleanUpTmpPhar, $restoreLimit): void {
            $cleanUpTmpPhar();
            $restoreLimit();
        };

        try {
            self::dumpPhar($outputDir, $box, $cleanUp);
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());

            return ExitCode::FAILURE;
        }

        return ExitCode::SUCCESS;
    }

    private static function getPharFilePath(IO $io): ?string
    {
        $filePath = realpath($io->getArgument(self::PHAR_ARG)->asString());

        if (false !== $filePath) {
            return $filePath;
        }

        $io->error(
            sprintf(
                'The file "%s" could not be found.',
                $io->getArgument(self::PHAR_ARG)->asRaw(),
            ),
        );

        return null;
    }

    /**
     * @return array{Box, callable(): void}|array{null, null}
     */
    private function getBox(string $filePath, IO $io): ?array
    {
        $tmpFile = create_temporary_phar($filePath);
        $cleanUp = static fn () => remove($tmpFile);

        try {
            return [
                Box::create($tmpFile),
                $cleanUp,
            ];
        } catch (Throwable $throwable) {
            // Continue
        }

        if ($io->isDebug()) {
            $cleanUp();

            throw new ConsoleRuntimeException(
                'The given file is not a valid PHAR',
                0,
                $throwable,
            );
        }

        $io->error('The given file is not a valid PHAR');

        $cleanUp();

        return [null, null];
    }

    /**
     * @param callable(): void $cleanUp
     */
    private static function dumpPhar(string $outputDir, Box $box, callable $cleanUp): void
    {
        try {
            remove($outputDir);

            $rootLength = strlen('phar://'.$box->getPhar()->getPath()) + 1;

            foreach (new RecursiveIteratorIterator($box->getPhar()) as $filePath) {
                dump_file(
                    $outputDir.'/'.substr($filePath->getPathname(), $rootLength),
                    (string) $filePath->getContent(),
                );
            }
        } finally {
            $cleanUp();
        }
    }
}
