const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const packagePath = path.join(root, 'package.json');
const pluginRoot = path.resolve(root, '../../..');
const pluginPackagePath = path.join(pluginRoot, 'tests', 'TestApplication', 'package.json');

if (!fs.existsSync(pluginPackagePath)) {
    process.exit(0);
}

const pluginPackage = JSON.parse(fs.readFileSync(pluginPackagePath, 'utf-8'));
const rootPackage = JSON.parse(fs.readFileSync(packagePath, 'utf-8'));

function mergeDependencies(target = {}, source = {}) {
    const result = { ...target };
    for (const [pkg, version] of Object.entries(source)) {
        result[pkg] = version;
    }

    return result;
}

rootPackage.dependencies = mergeDependencies(rootPackage.dependencies, pluginPackage.dependencies);
rootPackage.devDependencies = mergeDependencies(rootPackage.devDependencies, pluginPackage.devDependencies);

fs.writeFileSync(packagePath, JSON.stringify(rootPackage, null, 4));
