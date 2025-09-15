<?php
namespace Scavix\LocalRepository;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\PathRepository;
use Composer\Package\Package;

class LocalRepositoryPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $extra = $composer->getPackage()->getExtra();
        $paths = $extra['local-repositories'] ?? [];

        if (!is_array($paths) || empty($paths)) {
            $io->debug("No local repositories defined in extra.local-repositories.");
            return;
        }

        $repoManager = $composer->getRepositoryManager();
        $config = $composer->getConfig();
        $stabilityFlags = $composer->getPackage()->getStabilityFlags();

        foreach ($paths as $path) {
            $absolutePath = realpath($path);

            if ($absolutePath && is_dir($absolutePath)) {
                $io->write("<info>Local repository found: $absolutePath</info>");

                $repoManager->addRepository(
                    new PathRepository(
                        [
                            'type' => 'path',
                            'url' => $absolutePath,
                            'options' => ['symlink' => true]
                        ],
                        $io,
                        $config
                    )
                );

                $composerJson = $absolutePath . '/composer.json';
                if (is_file($composerJson)) {
                    $json = json_decode(file_get_contents($composerJson), true);
                    if (!empty($json['name'])) {
                        $packageName = $json['name'];
                        $io->debug("Allowing @dev for local package: $packageName");
                        $stabilityFlags[$packageName] = Package::STABILITY_DEV;
                    } else {
                        $io->debug("No name found in $composerJson, skipping stability flag.");
                    }
                } else {
                    $io->debug("No composer.json found in $absolutePath, skipping stability flag.");
                }

            } else {
                $io->debug("Local repository not found: $path");
            }
        }

        $composer->getPackage()->setStabilityFlags($stabilityFlags);
    }

    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}
}
