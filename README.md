# Setting up a new SynergyCP Package

 - Copy the contents of this repository to a new directory in the `packages` folder of the SynergyCP Application.
 - Placeholder text is distributed throughout the sourcecode:
   - Replace `<<PKG_LOWER>>` with the folder the package is stored in, e.g. `abuse`.
   - Replace `<<PKG_NAMESPACE>>` with `<<PKG_LOWER>>` converted to a PHP Namespace, e.g. `Abuse`.
   - Replace `<<PKG_FULL_NAME>>` with the full name of the package, e.g. `Abuse Reports`.
 - Add a feature and a Provider for it in the `app/` folder.
 - Add the Provider to `provides/Providers.php`.
 - Update the README.md file.
 - [Add the package](https://github.com/synergycp/scp-package-seed/wiki/Adding-a-Package-to-the-Application) to the Application.
 - [Read the Wiki](https://github.com/synergycp/scp-package-seed/wiki) to get familiar with the package structure.
