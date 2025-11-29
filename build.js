#!/usr/bin/env node
/**
 * Asset Build System
 * Minifies and bundles CSS/JS for production
 */

const fs = require('fs');
const path = require('path');
const CleanCSS = require('clean-css');
const { minify: minifyJS } = require('terser');

// Configuration
const config = {
    sourceDir: path.join(__dirname, 'app'),
    coreViewDir: path.join(__dirname, 'app/Core/View/view'),
    modulesDir: path.join(__dirname, 'app/Modules'),
    distDir: path.join(__dirname, 'pub/assets/dist'),
    publicAssetsDir: path.join(__dirname, 'pub/assets'),
    
    // Core CSS files (generic components)
    coreCssFiles: [
        'base/web/css/_reset.css',
        'base/web/css/_variables.css',
        'base/web/css/components/_buttons.css',
        'base/web/css/components/_forms.css',
        'base/web/css/components/_cards.css',
        'base/web/css/components/_grid.css',
        'base/web/css/components/_tables.css',
        'base/web/css/components/_alerts.css',
        'base/web/css/components/_modals.css',
        'base/web/css/components/_utilities.css',
        'frontend/web/css/layout.css'
    ],
    
    // Core JS files
    coreJsFiles: [
        'base/web/js/core.js'
    ]
};

// Ensure dist directory exists
function ensureDir(dir) {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
}

// Read file with error handling
function readFile(filePath) {
    try {
        return fs.readFileSync(filePath, 'utf8');
    } catch (error) {
        console.warn(`⚠️  Warning: Could not read ${filePath}`);
        return '';
    }
}

/**
 * Scan app/Modules/ for CSS files
 * Looks for: app/Modules/{Module}/view/frontend/web/css/*.css
 * Returns files in order: _variables.css first, then alphabetically
 */
function scanModuleCss() {
    const files = [];
    
    if (!fs.existsSync(config.modulesDir)) return files;
    
    const modules = fs.readdirSync(config.modulesDir).filter(m => {
        const modulePath = path.join(config.modulesDir, m);
        return fs.statSync(modulePath).isDirectory();
    });
    
    for (const module of modules) {
        const cssDir = path.join(config.modulesDir, module, 'view/frontend/web/css');
        if (!fs.existsSync(cssDir)) continue;
        
        const cssFiles = fs.readdirSync(cssDir)
            .filter(f => f.endsWith('.css'))
            .sort((a, b) => {
                // _variables.css first, then alphabetically
                if (a === '_variables.css') return -1;
                if (b === '_variables.css') return 1;
                return a.localeCompare(b);
            });
        
        for (const cssFile of cssFiles) {
            files.push({
                path: path.join(cssDir, cssFile),
                label: `${module}: ${cssFile}`
            });
        }
    }
    
    return files;
}

/**
 * Scan app/Modules/ for JS files
 * Looks for: app/Modules/{Module}/view/frontend/web/js/*.js
 */
function scanModuleJs() {
    const files = [];
    
    if (!fs.existsSync(config.modulesDir)) return files;
    
    const modules = fs.readdirSync(config.modulesDir).filter(m => {
        const modulePath = path.join(config.modulesDir, m);
        return fs.statSync(modulePath).isDirectory();
    });
    
    for (const module of modules) {
        const jsDir = path.join(config.modulesDir, module, 'view/frontend/web/js');
        if (!fs.existsSync(jsDir)) continue;
        
        const jsFiles = fs.readdirSync(jsDir)
            .filter(f => f.endsWith('.js'))
            .sort();
        
        for (const jsFile of jsFiles) {
            files.push({
                path: path.join(jsDir, jsFile),
                label: `${module}: ${jsFile}`
            });
        }
    }
    
    return files;
}

// Minify CSS from multiple sources
async function minifyCSS(files, outputName) {
    console.log(`🎨 Minifying CSS: ${outputName}`);
    
    let combinedCSS = '';
    
    for (const fileObj of files) {
        const content = readFile(fileObj.path);
        if (content) {
            combinedCSS += `\n/* ${fileObj.label} */\n${content}\n`;
            console.log(`  ✓ ${fileObj.label}`);
        }
    }
    
    const originalSize = Buffer.byteLength(combinedCSS, 'utf8');
    
    const output = new CleanCSS({
        level: 2,
        compatibility: 'ie11'
    }).minify(combinedCSS);
    
    if (output.errors.length > 0) {
        console.error('❌ CSS Errors:', output.errors);
        return false;
    }
    
    const outputPath = path.join(config.distDir, outputName);
    ensureDir(path.dirname(outputPath));
    fs.writeFileSync(outputPath, output.styles);
    
    const minifiedSize = Buffer.byteLength(output.styles, 'utf8');
    const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
    
    console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)`);
    return true;
}

// Minify JavaScript from multiple sources
async function minifyJavaScript(files, outputName) {
    console.log(`⚡ Minifying JS: ${outputName}`);
    
    let combinedJS = '';
    
    for (const fileObj of files) {
        const content = readFile(fileObj.path);
        if (content) {
            combinedJS += `\n/* ${fileObj.label} */\n${content}\n`;
            console.log(`  ✓ ${fileObj.label}`);
        }
    }
    
    const originalSize = Buffer.byteLength(combinedJS, 'utf8');
    
    try {
        const result = await minifyJS(combinedJS, {
            compress: {
                dead_code: true,
                drop_console: true,
                drop_debugger: true,
                conditionals: true,
                evaluate: true,
                booleans: true,
                loops: true,
                unused: true,
                hoist_funs: true,
                keep_fargs: false,
                hoist_vars: false,
                if_return: true,
                join_vars: true,
                side_effects: true,
                warnings: false
            },
            mangle: true,
            format: {
                comments: false
            }
        });
        
        const outputPath = path.join(config.distDir, outputName);
        ensureDir(path.dirname(outputPath));
        fs.writeFileSync(outputPath, result.code);
        
        const minifiedSize = Buffer.byteLength(result.code, 'utf8');
        const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
        
        console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)`);
        return true;
    } catch (error) {
        console.error('❌ JS Error:', error.message);
        return false;
    }
}

// Clean dist directory
function cleanDist() {
    if (fs.existsSync(config.distDir)) {
        fs.rmSync(config.distDir, { recursive: true, force: true });
    }
    ensureDir(config.distDir);
}

// Build critical CSS (header styles for above-the-fold)
async function buildCriticalCSS() {
    console.log('🎨 Building Critical CSS (header/hero for LCP)\n');
    
    const criticalFiles = [
        { path: path.join(config.coreViewDir, 'base/web/css/_variables.css'), label: 'Core: variables' }
    ];
    
    // Add Theme critical files if they exist
    const themeCriticalFiles = ['_variables.css', '_header.css', '_hero.css'];
    const themeDir = path.join(config.modulesDir, 'Theme/view/frontend/web/css');
    
    for (const file of themeCriticalFiles) {
        const filePath = path.join(themeDir, file);
        if (fs.existsSync(filePath)) {
            criticalFiles.push({ path: filePath, label: `Theme: ${file}` });
        }
    }
    
    return await minifyCSS(criticalFiles, 'critical.min.css');
}

// Main build process
async function build() {
    console.log('🚀 Starting Production Bundle Build\n');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    const startTime = Date.now();
    
    // Clean and create dist directory
    cleanDist();
    
    console.log('\n📦 Building Production Bundles\n');
    
    // Build critical CSS (header/hero for LCP)
    await buildCriticalCSS();
    
    // Build all-in-one CSS bundle
    const allCssFiles = [];
    
    // 1. Core CSS (from app/Core/View/view/)
    for (const file of config.coreCssFiles) {
        const filePath = path.join(config.coreViewDir, file);
        if (fs.existsSync(filePath)) {
            allCssFiles.push({
                path: filePath,
                label: `Core: ${file}`
            });
        }
    }
    
    // 2. Modules CSS (dynamically scan app/Modules/)
    const moduleCssFiles = scanModuleCss();
    allCssFiles.push(...moduleCssFiles);
    
    await minifyCSS(allCssFiles, 'all.min.css');
    
    // Build all-in-one JS bundle
    const allJsFiles = [];
    
    // 1. Core JS (from app/Core/View/view/)
    for (const file of config.coreJsFiles) {
        const filePath = path.join(config.coreViewDir, file);
        if (fs.existsSync(filePath)) {
            allJsFiles.push({
                path: filePath,
                label: `Core: ${file}`
            });
        }
    }
    
    // 2. Modules JS (dynamically scan app/Modules/)
    const moduleJsFiles = scanModuleJs();
    allJsFiles.push(...moduleJsFiles);
    
    await minifyJavaScript(allJsFiles, 'all.min.js');
    
    const duration = ((Date.now() - startTime) / 1000).toFixed(2);
    
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`✅ Build Complete in ${duration}s\n`);
    console.log('📁 Production Bundles:');
    console.log('  • pub/assets/dist/critical.min.css (inlined for instant LCP)');
    console.log('  • pub/assets/dist/all.min.css (loaded async)');
    console.log('  • pub/assets/dist/all.min.js (deferred)');
    console.log('\n🎯 Ready for deployment - no Node.js needed on server!\n');
}

// Run build
build().catch(error => {
    console.error('❌ Build failed:', error);
    process.exit(1);
});
