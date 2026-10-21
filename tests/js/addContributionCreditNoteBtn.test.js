'use strict';

const test = require('node:test');
const assert = require('node:assert');
const { buildContributionDom } = require('./fixtures');
const { addCreditNoteBtn } = require('../../js/addContributionCreditNoteBtn.js');

test('Create New Credit Note button attaches to the contribution form only, never the search form', () => {
  const { $, document } = buildContributionDom();

  addCreditNoteBtn($, '/civicrm/contribution/creditnote?reset=1&action=add&contribution_id=1');

  assert.strictEqual(
    document.querySelectorAll('#Search a.btn-creditnote-create').length,
    0,
    'button must NOT leak into the Find Contributions (#Search) form'
  );

  const inView = document.querySelectorAll('#ContributionView a.btn-creditnote-create');
  assert.strictEqual(inView.length, 1, 'button must attach to the contribution view form');
  assert.strictEqual(inView[0].querySelector('span').textContent, 'Create New Credit Note');
});

test('Create New Credit Note button href is preserved', () => {
  const { $, document } = buildContributionDom();

  addCreditNoteBtn($, '/civicrm/contribution/creditnote?reset=1&action=add&contribution_id=42');

  const link = document.querySelector('#ContributionView a.btn-creditnote-create');
  assert.strictEqual(link.getAttribute('href'), '/civicrm/contribution/creditnote?reset=1&action=add&contribution_id=42');
});
