const fs   = require('fs');
const path = require('path');
const nm   = path.join(__dirname, '..', 'node_modules');
const pub  = path.join(__dirname, '..', 'public');

const copies = [
    // Font files
    [path.join(nm, '@fortawesome/fontawesome-free/webfonts'), path.join(pub, 'webfonts')],
    [path.join(nm, 'bootstrap-icons/font/fonts'),             path.join(pub, 'fonts')],
    // Font Awesome CSS (vendor static)
    [path.join(nm, '@fortawesome/fontawesome-free/css'),      path.join(pub, 'vendor/fontawesome/css')],
    [path.join(nm, '@fortawesome/fontawesome-free/webfonts'), path.join(pub, 'vendor/fontawesome/webfonts')],
    // UMD JS builds (loaded synchronously in layout)
    [[path.join(nm, 'chart.js/dist/chart.umd.js')],                        path.join(pub, 'js/vendor'), 'chart.umd.js'],
    [[path.join(nm, 'tom-select/dist/js/tom-select.complete.min.js')],      path.join(pub, 'js/vendor'), 'tom-select.min.js'],
    [[path.join(nm, 'sweetalert2/dist/sweetalert2.all.min.js')],            path.join(pub, 'js/vendor'), 'sweetalert2.min.js'],
    [[path.join(nm, 'sortablejs/Sortable.min.js')],                         path.join(pub, 'js/vendor'), 'sortable.min.js'],
];

for (const entry of copies) {
    if (Array.isArray(entry[0])) {
        // Single-file copy: [srcArray, destDir, destName]
        const [srcArr, destDir, destName] = entry;
        fs.mkdirSync(destDir, { recursive: true });
        fs.copyFileSync(srcArr[0], path.join(destDir, destName));
        console.log(`Copied ${path.basename(srcArr[0])} → ${path.relative(process.cwd(), path.join(destDir, destName))}`);
    } else {
        // Directory copy: [srcDir, destDir]
        const [srcDir, destDir] = entry;
        fs.mkdirSync(destDir, { recursive: true });
        for (const f of fs.readdirSync(srcDir)) {
            fs.copyFileSync(path.join(srcDir, f), path.join(destDir, f));
        }
        console.log(`Copied ${path.relative(process.cwd(), srcDir)} → ${path.relative(process.cwd(), destDir)} (${fs.readdirSync(srcDir).length} files)`);
    }
}
