import assert from 'node:assert/strict';
import {createRequire} from 'node:module';
const require = createRequire(import.meta.url);
const {chromium} = require('../../empfängererklärung_form/.tools/browser/node_modules/playwright');
const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
let checks = 0;
function check(condition,label) { assert.ok(condition,label); checks++; console.log('PASS: '+label); }
try {
  const page = await browser.newPage({viewport:{width:1280,height:1000}}); const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('http://127.0.0.1:8097/?pagename=reklamation-mehrfach');
  const apps = page.locator('.rf-app');
  check(await apps.count() === 2,'both shortcode instances are rendered');
  await apps.nth(0).locator('.rf-form').waitFor({state:'visible'});
  await apps.nth(1).locator('.rf-form').waitFor({state:'visible'});
  check(await apps.nth(0).locator('[data-item]').count() === 1 && await apps.nth(1).locator('[data-item]').count() === 1,'both instances initialize independently');
  const firstId = await apps.nth(0).locator('[name=first_name]').getAttribute('id');
  const secondId = await apps.nth(1).locator('[name=first_name]').getAttribute('id');
  check(firstId !== secondId,'field IDs are unique across instances');
  await apps.nth(1).locator('[name=first_name]').fill('Nur zweite Instanz');
  check(await apps.nth(0).locator('[name=first_name]').inputValue() === '','inputs do not affect the other instance');
  await apps.nth(1).locator('[data-add-item]').click();
  check(await apps.nth(1).locator('[data-item]').count() === 2 && await apps.nth(0).locator('[data-item]').count() === 1,'dynamic article rows remain instance-local');
  check(errors.length === 0,'no browser runtime errors with two forms');
  console.log(`${checks} Mehrfachinstanz-Assertions bestanden.`);
} finally { await browser.close(); }
