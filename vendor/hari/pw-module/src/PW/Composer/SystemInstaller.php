<?php
namespace PW\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

class SystemInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // do the installation
        parent::install($repo, $package);
    }
    
    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // do the installation
        parent::uninstall($repo, $package);
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
