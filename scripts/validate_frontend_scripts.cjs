const fs = require('node:fs');

const pages = [
  'laravel/public/src/admin.html',
  'laravel/public/src/siswa.html',
];

for (const path of pages) {
  const html = fs.readFileSync(path, 'utf8');
  const scripts = Array.from(html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi));
  for (const script of scripts) {
    if (script[1].trim()) new Function(script[1]);
  }
  console.log(`Inline scripts valid: ${path}`);
}