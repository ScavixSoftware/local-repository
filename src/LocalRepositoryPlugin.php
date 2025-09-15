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
            $io->write("<info>No local repositories defined in extra.local-repositories.</info>");
            return;
        }

        $repoManager = $composer->getRepositoryManager();
        $config = $composer->getConfig();
		$stabilityFlags = $composer->getPackage()->getStabilityFlags();

        foreach ($paths as $path)
		{
            $absolutePath = realpath($path);

            foreach (glob($absolutePath . '/*', GLOB_ONLYDIR) as $subdir)
			{
				$composerJson = $subdir . '/composer.json';

                if (!is_file($composerJson)) {
                    $io->debug("Skipping directory (no composer.json): $subdir");
                    continue;
                }

                $pkgName = null;
                $json = json_decode(file_get_contents($composerJson), true);
                if (!empty($json['name'])) {
                    $pkgName = $json['name'];
                    $stabilityFlags[$pkgName] = Package::STABILITY_DEV;
					$pkgName .= "@".$json['version'];
                }

                $io->write("<info>Adding local package: " . ($pkgName ?? $subdir) . "</info>");

                $repoManager->prependRepository(
                    new PathRepository(
                        [
                            'type' => 'path',
                            'url' => $subdir,
                            'options' => ['symlink' => true]
                        ],
                        $io,
                        $config
                    )
                );
			}
        }
		$composer->getPackage()->setStabilityFlags($stabilityFlags);
    }

    public function deactivate(Composer $composer, IOInterface $io) {}
    public function uninstall(Composer $composer, IOInterface $io) {}
}
