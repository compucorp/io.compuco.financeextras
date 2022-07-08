## Finance extras

This extension provides CiviCRM finance improvement.

The extension must implement get_refunded_amount API to supports refund payment.

Currently only "Stripe Extension" supports refunded amount API. So, other payment processor only shows original amount as available amount.

## Implementation details
API action name : get_refunded_amount
Parameters : payment_processor_id , trxn_id

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.4+
* CiviCRM 5.39+

## Installation (Web UI)

Learn more about installing CiviCRM extensions in the [CiviCRM Sysadmin Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl io.compuco.financeextras@https://github.com/compucorp/io.compuco.financeextras/archive/master.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/compucorp/io.compuco.financeextras.git
cv en financeextras
```

