import fs from 'node:fs';

// A successful compiler exit must not hide an empty Tailwind source scan.
const manifest = JSON.parse(fs.readFileSync('public/build/manifest.json', 'utf8'));
for (const input of ['resources/css/app.css', 'resources/css/auth.css']) {
    const file = manifest[input]?.file;
    if (!file) throw new Error(`Missing CSS entry: ${input}`);
    const css = fs.readFileSync(`public/build/${file}`, 'utf8');
    const selectors = input.endsWith('/app.css') ? ['.flex{', '.h-8{'] : ['.flex{'];
    for (const selector of selectors) {
        if (!css.includes(selector)) throw new Error(`Tailwind utilities missing in ${file}: ${selector}. Check source scan permissions before publishing this build.`);
    }
}
console.log('OK CSS: Tailwind utilities present in application and login assets.');
