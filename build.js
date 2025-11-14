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
    sourceDir: path.join(__dirname, 'pub/assets'),
    distDir: path.join(__dirname, 'pub/assets/dist'),
    cssFiles: [
        // Base CSS (critical path - loaded first)
        'base/css/critical-hero.css',
        'base/css/reset.css',
        'base/css/variables.css',
        'base/css/base.css',
        // Frontend CSS
        'frontend/css/theme.css'
    ],
    jsFiles: [
        // Base JS
        'base/js/base.js',
        // Frontend JS
        'frontend/js/theme.js'
    ],
    modules: [
        'home',
        'about',
        'services',
        'contact',
        'error',
        'head',
        'footer'
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

// Minify CSS
async function minifyCSS(files, outputName) {
    console.log(`\n🎨 Minifying CSS: ${outputName}`);
    
    let combinedCSS = '';
    const cleanCSS = new CleanCSS({
        level: 2,
        compatibility: 'ie11'
    });
    
    for (const file of files) {
        const filePath = path.join(config.sourceDir, file);
        const content = readFile(filePath);
        if (content) {
            combinedCSS += `\n/* ${file} */\n${content}\n`;
            console.log(`  ✓ ${file}`);
        }
    }
    
    const result = cleanCSS.minify(combinedCSS);
    
    if (result.errors.length > 0) {
        console.error('❌ CSS Errors:', result.errors);
        return false;
    }
    
    const outputPath = path.join(config.distDir, outputName);
    ensureDir(path.dirname(outputPath));
    fs.writeFileSync(outputPath, result.styles);
    
    const originalSize = Buffer.byteLength(combinedCSS, 'utf8');
    const minifiedSize = Buffer.byteLength(result.styles, 'utf8');
    const savings = ((1 - minifiedSize / originalSize) * 100).toFixed(1);
    
    console.log(`  📦 ${(originalSize / 1024).toFixed(1)}KB → ${(minifiedSize / 1024).toFixed(1)}KB (${savings}% smaller)`);
    return true;
}

// Minify JS
async function minifyJavaScript(files, outputName) {
    console.log(`\n⚡ Minifying JS: ${outputName}`);
    
    let combinedJS = '';
    
    for (const file of files) {
        const filePath = path.join(config.sourceDir, file);
        const content = readFile(filePath);
        if (content) {
            combinedJS += `\n/* ${file} */\n${content}\n`;
            console.log(`  ✓ ${file}`);
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

// Process module assets
async function processModules() {
    console.log('\n📦 Processing Module Assets');
    
    for (const module of config.modules) {
        // Module CSS - handle special case for head module (uses header.css)
        const cssFilename = module === 'head' ? 'header' : module;
        const cssPath = `modules/${module}/view/frontend/css/${cssFilename}.css`;
        const cssFiles = [cssPath];
        await minifyCSS(cssFiles, `modules/${module}.min.css`);
        
        // Module JS - handle special case for head module (uses header.js)
        const jsFilename = module === 'head' ? 'header' : module;
        const jsPath = `modules/${module}/view/frontend/js/${jsFilename}.js`;
        const jsFilePath = path.join(config.sourceDir, jsPath);
        
        if (fs.existsSync(jsFilePath)) {
            const jsFiles = [jsPath];
            await minifyJavaScript(jsFiles, `modules/${module}.min.js`);
        }
    }
}

// Main build process
async function build() {
    console.log('🚀 Starting Build Process\n');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    const startTime = Date.now();
    
    // Create dist directory
    ensureDir(config.distDir);
    
    // Build base CSS bundle
    await minifyCSS(config.cssFiles, 'base.min.css');
    
    // Build base JS bundle
    await minifyJavaScript(config.jsFiles, 'base.min.js');
    
    // Process modules
    await processModules();
    
    // Build all-in-one CSS bundle (base + all modules for zero render blocking)
    const allCssFiles = [...config.cssFiles];
    for (const module of config.modules) {
        const cssFilename = module === 'head' ? 'header' : module;
        allCssFiles.push(`modules/${module}/view/frontend/css/${cssFilename}.css`);
    }
    await minifyCSS(allCssFiles, 'all.min.css');
    
    // Build all-in-one JS bundle
    const allJsFiles = [...config.jsFiles];
    for (const module of config.modules) {
        const jsFilename = module === 'head' ? 'header' : module;
        const jsPath = `modules/${module}/view/frontend/js/${jsFilename}.js`;
        const jsFilePath = path.join(config.sourceDir, jsPath);
        if (fs.existsSync(jsFilePath)) {
            allJsFiles.push(jsPath);
        }
    }
    await minifyJavaScript(allJsFiles, 'all.min.js');
    
    const duration = ((Date.now() - startTime) / 1000).toFixed(2);
    
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`✅ Build Complete in ${duration}s\n`);
    console.log('📁 Output: pub/assets/dist/');
    console.log('\nNext steps:');
    console.log('  1. Update Assets.php to use minified files in production');
    console.log('  2. Test with: NODE_ENV=production php -S localhost:8000 -t pub');
    console.log('  3. Deploy to production\n');
}

// Run build
build().catch(error => {
    console.error('❌ Build failed:', error);
    process.exit(1);
});
