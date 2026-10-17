# Skillspot marketplace operations

## Release order

1. Record the release commit and `public/build/manifest.json` hash. Keep the current production checkout and its `.env` backed up outside the web root.
2. Take and verify a private database backup and an uploaded-file backup. Restore both to staging before running migrations there.
3. On staging, run `php artisan migrate:status`, review every pending migration, then run `php artisan migrate --force`. Run `php artisan test` against the staging MySQL configuration and `npm run build` in a Node-capable environment.
4. Test creator approval, course review, free enrollment, Razorpay test payment and signed webhook, lesson completion, certificate, refund, and an individual and organization payout batch. Check that each sale and refund matches the enrollment and earnings ledger.
5. Disable paid checkout before production deployment. Deploy the reviewed source and built `public/build`, preserving `.env` and uploaded files. Confirm `public/db_test.php` and `public/run_migrations.php` are absent. Back up production again, run `php artisan migrate:status`, then `php artisan migrate --force` only after all pending migrations match the reviewed release.
6. Run `php artisan optimize:clear`, verify the deployed revision and manifest, and smoke-test login, course access, media, mail, and marketplace routes. Enable paid checkout only after the payment and payout gates pass. Restore the prior release and backups if auth, course access, or financial records fail verification.

Never run `db:seed` on production. The seeder only initializes roles, permissions, and demo course content; administrators must be created through a private account workflow with unique passwords.

## Secrets and backups

- Set database, R2, mail, and Razorpay credentials in the private server environment. Do not store them in Git, command history, web-accessible files, or support tickets.
- Rotate any previously embedded R2 key, and reset the privileged accounts that could have used a default password. Review server access logs for requests to the former public diagnostic and migration scripts.
- Keep database and uploaded-file backups together, encrypted and outside `public/`. Test a restore on staging. Do not rotate `APP_KEY` without migrating data encrypted under the current key.

## Background work and monitoring

- Run `php artisan schedule:run` once per minute on the hosting scheduler. It releases earnings after the seven-day refund hold. If `QUEUE_CONNECTION=database`, keep a queue worker running and monitor failed jobs.
- Monitor application errors, failed webhooks, pending refund requests, and the gap between Razorpay captured payments and local paid orders. Check that paid orders, enrollments, marketplace sales, ledger entries, and payout items reconcile.
- Verify SMTP delivery and R2 upload/download using designated test accounts after each release.

## Marketplace responsibility

- An admin reviews instructor applications and course submissions. An admin verifies an organization's status and bank details before preparing its payout.
- Razorpay is the only checkout provider. Configure its signed webhook at `/webhook/razorpay`; do not grant enrollment from an unsigned callback.
- A refund request can remain pending after a provider timeout. In Admin → Payments, use **Check refund with Razorpay**. A processed full refund revokes enrollment and reverses earnings; an outstanding refund remains blocked. A provider response with no refund or only failed refunds unlocks a new request after 24 hours. If Razorpay reports other or incomplete refunds, investigate in Razorpay before retrying.
- Seller payouts are manual bank transfers. Wait for the refund hold, prepare one batch per payee, send the transfer outside the LMS, then record the bank reference and confirm the transfer before marking the batch paid. Reconcile the bank statement against payout items and ledger entries.

## Final-product gate

After the marketplace MVP is stable, review real learner and seller journeys for usability, accessibility, performance, support load, reports, and restore time. Track findings against measured failures. Frappe feature parity and additional gateways require separate product decisions and complete payment integrations.
