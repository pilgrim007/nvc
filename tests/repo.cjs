const fs = require('node:fs');
const assert = require('node:assert/strict');
const path = require('node:path');
const htmls = fs.readdirSync('.').filter(p => p.endsWith('.html'));
let forms = 0;
for (const file of htmls) {
  const html = fs.readFileSync(file, 'utf8');
  for (const [, url] of html.matchAll(/(?:href|src|action)="([^"]+)"/g)) {
    assert(!url.startsWith('http:'), file + ': insecure URL');
    if (/^(?:[a-z]+:|#|\/\/)/i.test(url)) continue;
    const target = url.split(/[?#]/)[0];
    assert(fs.existsSync(path.resolve(target)), file + ': missing ' + target);
  }
  for (const [form] of html.matchAll(/<form\b[^>]*>/g)) {
    forms++;
    assert(form.includes('action="https://test.nvc.ee/send.php"'));
    assert(form.includes('method="post"'));
    assert(!form.includes('mailto:'));
  }
}
assert.equal(forms, 1);
assert.equal(fs.readFileSync('index.html', 'utf8'), fs.readFileSync('landing-variant-1.html', 'utf8'));
console.log('All ' + htmls.length + ' HTML pages: local targets exist, HTTPS URLs and one safe POST form.');
