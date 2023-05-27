<?php
namespace PW\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use React\Promise\PromiseInterface;

class SystemInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // do the installation
        $promise = parent::install($repo, $package);
        $installPath = $this->getPackageBasePath($package);
        list(, $name) = $this->getVendorAndName($package);
        $outputStatus = function () use ($installPath, $name) {
            $filesExists = false;
            foreach (['module', 'module.php'] as $ext) {
                if(file_exists("{$installPath}/{$name}.{$ext}")) {
                    $filesExists = true;
                    break;
                }
            }
            if(!$filesExists) {
                $this->io->write(sprintf('<error>Files in "%s" not created</error>', $installPath));
            }
        };

        // Composer v2 might return a promise here
        if ($promise instanceof PromiseInterface) {
            return $promise->then($outputStatus);
        }

        // If not, execute the code right away as parent::uninstall executed synchronously (composer v1, or v2 without async)
        $outputStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // do the installation
        $promise = parent::uninstall($repo, $package);
        $installPath = $this->getPackageBasePath($package);
        $outputStatus = function () use ($installPath) {
            if (is_dir($installPath)) {
                $this->io->write(sprintf('<warning>Files in "%s" where not deleted</warning>', $installPath));
            }
        };

        // Composer v2 might return a promise here
        if ($promise instanceof PromiseInterface) {
            return $promise->then($outputStatus);
        }

        // If not, execute the code right away as parent::uninstall executed synchronously (composer v1, or v2 without async)
        $outputStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        list($vendor, $name) = $this->getVendorAndName($package);
        return "site/modules/{$name}";
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageBasePath(PackageInterface $package)
    {
        list($vendor, $name) = $this->getVendorAndName($package);
        return "site/modules/{$name}";
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType == 'pw-module';
    }

    /**
     *
     * Given a PackageInterface, returns the package vendor and name.
     *
     * @param PackageInterface $package The package to work with.
     *
     * @return array An array where element 0 is the package vendor and
     * element 1 is the package name.
     *
     */
    private function getVendorAndName(PackageInterface $package)
    {
        list($vendor, $name) = explode('/', $package->getPrettyName());
        $vendor = $this->ucSnakeWords($vendor);
        $name = $this->ucSnakeWords($name);
        return array($vendor, $name);
    }

    /**
     *
     * Converts "this-text" to "This_Text".
     *
     * @param string $text The string to convert.
     *
     * @return string The converted string.
     *
     */
    private function ucSnakeWords($text)
    {
        $text = str_replace('-', ' ', $text);
        $text = ucwords($text);
        return str_replace(' ', '', $text);
    }
}
