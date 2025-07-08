const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const packageDistPath = path.join(root, 'package.json.dist');
const packagePath = path.join(root, 'package.json');
const pluginRoot = path.resolve(root, '../../..');
const pluginPackagePath = path.join(pluginRoot, 'tests', 'TestApplication', 'package.json');

const pluginPackageExists = fs.existsSync(pluginPackagePath);
const pluginPackage = pluginPackageExists
    ? JSON.parse(fs.readFileSync(pluginPackagePath, 'utf-8'))
    : {
          dependencies: {},
          devDependencies: {},
          removeDependencies: [],
          removeDevDependencies: [],
      };

const basePackage = JSON.parse(fs.readFileSync(packageDistPath, 'utf-8'));

function mergeDependencies(target = {}, source = {}) {
    const result = { ...target };
    for (const [pkg, version] of Object.entries(source)) {
        result[pkg] = version;
    }

    return result;
}

function removeDependencies(target = {}, packages = []) {
    const result = { ...target };

    for (const pkg of packages) {
        delete result[pkg];
    }

    return result;
}

const finalPackage = {
    ...basePackage,
    dependencies: removeDependencies(
        mergeDependencies(basePackage.dependencies, pluginPackage.dependencies),
        pluginPackage.removeDependencies ?? []
    ),
    devDependencies: removeDependencies(
        mergeDependencies(basePackage.devDependencies, pluginPackage.devDependencies),
        pluginPackage.removeDevDependencies ?? []
    ),
};

fs.writeFileSync(packagePath, JSON.stringify(finalPackage, null, 4));
