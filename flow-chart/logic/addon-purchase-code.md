# Active Site Logic — Add-on Purchase Code

Logic for calling `active_site` and validating purchase codes for LearnPress add-ons. **Does not** describe the UI input steps.

## Flow overview

```mermaid
flowchart TD
    Start([User has a purchase code]) --> Source{Source / action}

    Source -->|Upload zip with purchase-code.txt| Zip[learnpress.php upgrader_process_complete]
    Zip --> Read[Read purchase-code.txt]
    Read --> Active1[AddonService::active_site]
    Active1 --> Save1[Save / overwrite key in purchase_addons option]
    Save1 --> Clear1[Clear addons_purchased_info cache]
    Clear1 --> Done([Done])

    Source -->|Add-ons page: action_type = update-purchase| UP1[AddonsAjax case update-purchase]
    UP1 --> KeySource{Key source}
    KeySource -->|Pre-filled from DB| Old1[Old key]
    KeySource -->|User input| New1[New key]
    Old1 --> Active2[active_site]
    New1 --> Active2
    Active2 --> Save2[Save / overwrite key]
    Save2 --> Clear2[Clear cache]
    Clear2 --> Info2[get_addon_purchase_info]
    Info2 --> Done

    Source -->|Add-ons page: action_type = install<br/>paid ThimPress add-on| Inst1[AddonsAjax case install]
    Inst1 --> Input1[Enter / pre-fill key]
    Input1 --> Active3[active_site]
    Active3 --> Save3[Save / overwrite key]
    Save3 --> Clear3[Clear cache]
    Clear3 --> Info3[get_addon_purchase_info]
    Info3 --> Download1[download_from_thimpress]
    Download1 --> Install1[install + activate]
    Install1 --> Done

    Source -->|Add-ons page: action_type = update<br/>paid ThimPress add-on| Upd1[AddonsAjax case update]
    Upd1 --> GetKey[Use stored key if input empty]
    GetKey --> Info4[get_addon_purchase_info]
    Info4 --> Download2[download_from_thimpress]
    Download2 --> Update1[update plugin]
    Update1 --> Done

    Source -->|Add-ons page: free / wordpress.org add-on| Free[AddonsAjax case install/update]
    Free --> Direct[Download from wordpress.org]
    Direct --> InstallUpdate[install / update]
    InstallUpdate --> Done
```

## What each service method does

- **`AddonService::active_site()`** — registers the site with the ThimPress server, saves/overwrites the purchase code in the `purchase_addons` option, and clears the `addons_purchased_info` cache. Called for `install` and `update-purchase` actions.
- **`AddonService::get_addon_purchase_info()`** — validates the purchase code against the `info-addons-purchased` endpoint and returns the purchase info object. It no longer persists the key or clears the cache.
- **`AddonService::download_from_thimpress()`** — downloads the ZIP from the `download-addon` endpoint; for paid add-ons it includes `purchase_code` in the request body. It does **not** update the `purchase_addons` option anymore.
- **`AddonService::install()` / `update()`** — run `Plugin_Upgrader` and activate/re-activate the plugin.

## Code locations

| Scenario | File | Code area | Notes |
|---|---|---|---|
| Upload zip with `purchase-code.txt` | `learnpress.php` | `add_action( 'upgrader_process_complete', ... )` | Reads `purchase-code.txt` and calls `AddonService::active_site()` only. |
| `update-purchase` | `inc/Ajax/AddonsAjax.php` | `case 'update-purchase'` | Calls `active_site()` then `get_addon_purchase_info()`. |
| `install` paid add-on | `inc/Ajax/AddonsAjax.php` | `case 'install'` | Calls `active_site()` → `get_addon_purchase_info()` → `download_from_thimpress()` → `install()`. |
| `update` paid add-on | `inc/Ajax/AddonsAjax.php` | `case 'update'` | Uses stored key if input is empty, then `get_addon_purchase_info()` → `download_from_thimpress()` → `update()`. Does **not** call `active_site()`. |
| Free / wordpress.org add-on | `inc/Ajax/AddonsAjax.php` | `case 'install'` / `case 'update'` | Skips `active_site()` / `get_addon_purchase_info()`; downloads directly from wordpress.org. |

## Notes

- The `update` action is a plugin-version update, not a purchase-code re-activation. It only fetches the stored key when the input is empty.
- After the paid `install` flow, WordPress also fires `upgrader_process_complete`; if the downloaded ZIP contains `purchase-code.txt`, the learnpress.php hook calls `active_site()` again (idempotent save/clear).
- `download_from_thimpress()` should not persist the key; persistence belongs in `active_site()` or, if needed for `update`, in `get_addon_purchase_info()`.
