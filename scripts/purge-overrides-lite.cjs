/**
 * Very conservative CSS "purge" for theme-overrides.css.
 *
 * - No external deps (works in a Shopify theme folder).
 * - Removes only rules where ALL class selectors appear nowhere in the theme sources.
 * - Keeps any rule that has no class selectors (element selectors, :root, etc.).
 * - Keeps any class matching safelist patterns (Bootstrap-ish, Shopify-ish, rs- namespace, state classes).
 *
 * This avoids breaking dynamic classes (JS toggles, Bootstrap, Shopify editor).
 */

const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const CSS_IN = path.join(ROOT, 'assets', 'theme-overrides.css');
const CSS_OUT = path.join(ROOT, 'assets', 'theme-overrides.css'); // in-place

const CONTENT_GLOBS = [
  path.join(ROOT, 'layout'),
  path.join(ROOT, 'sections'),
  path.join(ROOT, 'snippets'),
  path.join(ROOT, 'templates'),
  path.join(ROOT, 'assets'),
];

const KEEP_CLASS_PATTERNS = [
  /^rs-/,
  /^shopify-/,
  /^template-/,
  /^btn/,
  /^container$/,
  /^row$/,
  /^col/,
  /^d-/,
  /^g-/,
  /^gap-/,
  /^(p|px|py|pt|pb|m|mx|my|mt|mb)-/,
  /^text-/,
  /^bg-/,
  /^border/,
  /^rounded/,
  /^shadow/,
  /^position-/,
  /^sticky/,
  /^fixed/,
  /^(top|start|end)-/,
  /^w-/,
  /^h-/,
  /^align-/,
  /^justify-/,
  /^(show|active|fade|collapse|dropdown|modal|offcanvas|carousel)$/,
];

function walkFiles(dir, out) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const ent of entries) {
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) {
      // skip huge irrelevant dirs if any
      if (ent.name === 'node_modules') continue;
      walkFiles(p, out);
    } else if (ent.isFile()) {
      const ext = path.extname(ent.name).toLowerCase();
      if (['.liquid', '.json', '.js', '.css'].includes(ext)) out.push(p);
    }
  }
}

function buildHaystack() {
  const files = [];
  for (const d of CONTENT_GLOBS) {
    if (fs.existsSync(d)) walkFiles(d, files);
  }
  let big = '';
  for (const f of files) {
    try {
      big += '\n' + fs.readFileSync(f, 'utf8');
    } catch {
      // ignore unreadable
    }
  }
  return big;
}

function extractClassNames(selectorText) {
  // Capture .className but avoid .5 or .\:
  const re = /\.([a-zA-Z_][\w-]*)/g;
  const classes = new Set();
  let m;
  while ((m = re.exec(selectorText))) classes.add(m[1]);
  return [...classes];
}

function isSafelisted(cls) {
  return KEEP_CLASS_PATTERNS.some((r) => r.test(cls));
}

function keepRule(rule, haystack) {
  // Keep @ rules always (we won't try to parse nested rules)
  if (rule.trimStart().startsWith('@')) return true;

  const braceIdx = rule.indexOf('{');
  if (braceIdx === -1) return true;
  const selectorPart = rule.slice(0, braceIdx);

  const classes = extractClassNames(selectorPart).filter((c) => !isSafelisted(c));
  if (classes.length === 0) return true; // no class selectors → keep

  // Remove only if *all* non-safelisted classes are absent from content
  return classes.some((c) => haystack.includes(c));
}

function splitTopLevelRules(css) {
  // Naive but robust enough for our custom overrides:
  // scan braces depth, split when depth returns to 0.
  const rules = [];
  let buf = '';
  let depth = 0;
  let inStr = false;
  let strChar = '';

  for (let i = 0; i < css.length; i++) {
    const ch = css[i];
    const prev = css[i - 1];
    buf += ch;

    if (!inStr && (ch === '"' || ch === "'") && prev !== '\\') {
      inStr = true;
      strChar = ch;
      continue;
    }
    if (inStr) {
      if (ch === strChar && prev !== '\\') inStr = false;
      continue;
    }

    if (ch === '{') depth++;
    if (ch === '}') depth = Math.max(0, depth - 1);
    if (depth === 0 && ch === '}') {
      rules.push(buf);
      buf = '';
    }
  }
  if (buf.trim()) rules.push(buf);
  return rules;
}

function main() {
  const before = fs.readFileSync(CSS_IN, 'utf8');
  const haystack = buildHaystack();
  const rules = splitTopLevelRules(before);

  const kept = [];
  const removed = [];
  for (const r of rules) {
    if (keepRule(r, haystack)) kept.push(r);
    else removed.push(r);
  }

  const after = kept.join('');
  fs.writeFileSync(CSS_OUT, after, 'utf8');

  const reportPath = path.join(ROOT, 'scripts', 'purge-overrides-lite.report.txt');
  const report =
    `theme-overrides.css purge-lite\\n` +
    `kept_rules=${kept.length}\\nremoved_rules=${removed.length}\\n` +
    `before_bytes=${Buffer.byteLength(before, 'utf8')}\\nafter_bytes=${Buffer.byteLength(after, 'utf8')}\\n`;
  fs.writeFileSync(reportPath, report, 'utf8');

  // eslint-disable-next-line no-console
  console.log(report.trim());
}

main();

