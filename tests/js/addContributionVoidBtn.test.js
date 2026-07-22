'use strict';

const test = require('node:test');
const assert = require('node:assert');
const { buildContributionDom } = require('./fixtures');
const { addContributionVoidBtn } = require('../../js/addContributionVoidBtn.js');

test('Void Contribution button attaches to the contribution form only, never the search form', () => {
  const { $, document } = buildContributionDom();

  addContributionVoidBtn($, '/civicrm/financeextras/contribution/void?reset=1&action=void&id=1');

  assert.strictEqual(
    document.querySelectorAll('#Search a.btn-creditnote-create').length,
    0,
    'button must NOT leak into the Find Contributions (#Search) form'
  );

  const inView = document.querySelectorAll('#ContributionView a.btn-creditnote-create');
  assert.strictEqual(inView.length, 1, 'button must attach to the contribution view form');
  assert.strictEqual(inView[0].querySelector('span').textContent, 'Void Contribution');
});

test('Void Contribution button href is preserved', () => {
  const { $, document } = buildContributionDom();

  addContributionVoidBtn($, '/civicrm/financeextras/contribution/void?reset=1&action=void&id=42');

  const link = document.querySelector('#ContributionView a.btn-creditnote-create');
  assert.strictEqual(link.getAttribute('href'), '/civicrm/financeextras/contribution/void?reset=1&action=void&id=42');
});
