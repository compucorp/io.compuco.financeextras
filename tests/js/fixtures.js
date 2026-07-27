'use strict';

const { JSDOM } = require('jsdom');

// Stub CiviCRM's global ts() (i18n) so the button scripts under test, which
// wrap their labels in ts(), don't throw a ReferenceError under jsdom/node.
global.ts = (str) => str;

/**
 * Builds a DOM that contains BOTH:
 *   - a Find Contributions search form (#Search) whose row contains the text
 *     "Contribution Status" but NOT the contribution status-row class, and
 *   - a contribution View form (#ContributionView) whose status row carries
 *     the `crm-contribution-form-block-contribution_status_id` class.
 *
 * This mirrors the real leak scenario: a contribution View pop-up injects the
 * button script into the underlying search page. The old (loose `:contains`)
 * selector matched the search row too; the fix must match the contribution
 * form row only.
 *
 * @returns {{dom: object, $: Function, document: Document}}
 */
function buildContributionDom () {
  const dom = new JSDOM(`<!DOCTYPE html><body>
    <form id="Search" class="CRM_Contribute_Form_Search"><table><tbody>
      <tr>
        <td><label>Contribution Amounts</label></td>
        <td><label>Contribution Status</label><select><option>Completed</option></select></td>
      </tr>
    </tbody></table></form>
    <form id="ContributionView" class="CRM_Contribute_Form_ContributionView"><table><tbody>
      <tr class="crm-contribution-form-block-contribution_status_id">
        <td>Contribution Status</td>
        <td>Completed</td>
      </tr>
    </tbody></table></form>
  </body>`);

  const $ = require('jquery')(dom.window);

  return { dom, $, document: dom.window.document };
}

module.exports = { buildContributionDom };
