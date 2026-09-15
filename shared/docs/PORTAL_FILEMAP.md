# YFD — Peta File Path (Dashboard, Bot, AI)

Referensi cepat untuk handover / IT baru. Path relatif dari root repo.

---

## 1. Portal web (Laravel)

| Modul | Path utama |
|---|---|
| Routes portal | `apps/admin-laravel/routes/web.php` (grup `portal.*`) |
| Controller dashboard | `apps/admin-laravel/app/Http/Controllers/Portal/DashboardController.php` |
| Financial Health Dashboard (UI) | `apps/admin-laravel/resources/views/portal/dashboard.blade.php` |
| Behavioral Dashboard (UI) | `apps/admin-laravel/resources/views/portal/emotional.blade.php` |
| Layout / sidebar portal | `apps/admin-laravel/resources/views/portal/layouts/app.blade.php` |
| Sidebar nav | `apps/admin-laravel/resources/views/portal/partials/sidebar-nav.blade.php` |
| KPI keuangan | `apps/admin-laravel/resources/views/portal/partials/financial-dashboard-kpi.blade.php` |
| Transaksi / input data | `apps/admin-laravel/resources/views/portal/transactions.blade.php` |
| Controller transaksi | `apps/admin-laravel/app/Http/Controllers/Portal/TransactionsController.php` |
| Premium index (placeholder) | `apps/admin-laravel/resources/views/portal/premium.blade.php` |
| Referral portal | `apps/admin-laravel/resources/views/portal/affiliate.blade.php` |
| Controller referral | `apps/admin-laravel/app/Http/Controllers/Portal/AffiliateController.php` |

### Route penting (portal)
| Fitur | Route name | URL |
|---|---|---|
| Financial Health | `portal.dashboard` | `/portal/dashboard` |
| Behavioral | `portal.emotional` | `/portal/emotional` |
| Input data | `portal.transactions` | `/portal/transaksi` |
| Referral | `portal.affiliate` | `/portal/referral` |
| Baseline / FTSA | `portal.baseline`, `portal.ftsa.create` | `/portal/baseline`, `/portal/ftsa/baru` |
| Manual gen financial | `portal.dashboard.generate-manual` | `POST /portal/dashboard/generate-manual` |
| Manual gen behavioral | `portal.emotional.generate-manual` | `POST /portal/emotional/generate-manual` |

---

## 2. Doctor’s Note & Clinical / Behavioral AI

| Peran | Path |
|---|---|
| Orkestrasi AI guidance | `apps/admin-laravel/app/Services/PortalAiGuidanceService.php` |
| Metrik + fallback financial | `apps/admin-laravel/app/Services/TransactionDashboardService.php` |
| Behavioral assessment + note | `apps/admin-laravel/app/Services/ImpulsivityAssessmentService.php` |
| Batch akhir bulan | `apps/admin-laravel/app/Services/PortalGuidanceBatchService.php` |
| Simpan/baca snapshot | `apps/admin-laravel/app/Services/PortalGuidanceSnapshotService.php` |
| Model snapshot | `apps/admin-laravel/app/Models/PortalGuidanceSnapshot.php` |
| Tabel DB | `portal_guidance_snapshots` (migration di `database/migrations/`) |
| Claude JSON (portal) | `apps/admin-laravel/app/Services/ClaudeJsonService.php` |
| Config AI / rules | `apps/admin-laravel/config/portal_ai.php` |
| Config brand Doctor’s Note | `apps/admin-laravel/config/portal.php` → `doctors_note` |
| Partial branding UI | `apps/admin-laravel/resources/views/portal/partials/doctors-note-brand.blade.php` |
| Disclaimer AI | `apps/admin-laravel/resources/views/portal/partials/ai-guidance-disclaimer.blade.php` |

### Tipe snapshot
- `doctors_note_monthly` — Doctor’s Note financial
- `clinical_summary_weekly` — Clinical summary mingguan
- `behavioral_monthly` — rekomendasi / note behavioral

---

## 3. FTSA / Baseline / Onboarding portal

| Peran | Path |
|---|---|
| FTSA form / baseline | `apps/admin-laravel/resources/views/portal/baseline/` |
| Controller baseline | `apps/admin-laravel/app/Http/Controllers/Portal/BaselineController.php` |
| Onboarding service | `apps/admin-laravel/app/Services/PortalOnboardingService.php` |
| FTSA AI guidance | `apps/admin-laravel/app/Services/FtsaAiGuidanceService.php` |
| Config kuesioner | `apps/admin-laravel/config/baseline_assessment.php` |

---

## 4. Checkout / produk / referral (website)

| Peran | Path |
|---|---|
| Checkout UI | `apps/admin-laravel/resources/views/Companyprofile/checkout.blade.php` |
| Checkout controller | `apps/admin-laravel/app/Http/Controllers/CheckoutController.php` |
| Affiliate / referral logic | `apps/admin-laravel/app/Services/AffiliateService.php` |
| Produk digital seeder | `apps/admin-laravel/database/seeders/DigitalProductSeeder.php` |
| Halaman produk | `apps/admin-laravel/resources/views/Companyprofile/produk.blade.php` |
| Layanan (hero) | `apps/admin-laravel/resources/views/Companyprofile/layanan.blade.php` |

### Kode produk penting
| Code | Arti |
|---|---|
| `yfd-bot-telegram` | First Aid |
| `yfd-first-aid-ftsa` | Bundle First Aid + FTSA |
| `yfd-ftsa-premium` | FTSA only |
| `yfd-bot-admin-monthly` | Admin bulanan (saat ini nonaktif) |
| `yfd-bot-admin-yearly` | Admin tahunan (saat ini nonaktif) |

---

## 5. Bot Telegram → AI

| Peran | Path |
|---|---|
| Entry + orkestrasi | `apps/bot-python/bot.py` |
| Claude API | `apps/bot-python/claude_ai.py` |
| System prompt kategori | `apps/bot-python/transaction_categories.py` |
| Rules konteks | `apps/bot-python/context_rules.py` |
| Klarifikasi | `apps/bot-python/clarification_rules.py` |
| Impulsif | `apps/bot-python/impulsive_rules.py` |
| Preview + save ke Laravel | `apps/bot-python/transaction_store.py` |
| Kuota AI | `apps/bot-python/ai_quota.py` |
| Env model | `apps/bot-python/.env` → `CLAUDE_MODELS`, `ANTHROPIC_API_KEY` |

### Model AI default
1. `claude-haiku-4-5`
2. `claude-sonnet-4-6` (fallback)

---

## 6. Admin panel

| Peran | Path |
|---|---|
| AdminLTE config | `apps/admin-laravel/config/adminlte.php` |
| Orders | `apps/admin-laravel/resources/views/admin/orders/` |
| Digital products | `apps/admin-laravel/resources/views/admin/digital_products/` |
| FTSA results | `apps/admin-laravel/resources/views/admin/ftsa_results/` |

---

## 7. Test cases

| Stack | Path |
|---|---|
| Laravel PHPUnit | `apps/admin-laravel/tests/Feature/`, `tests/Unit/` |
| Bot Python | `apps/bot-python/test_*.py` |

```bash
# Laravel
cd apps/admin-laravel && php artisan test

# Bot
cd apps/bot-python && python -m pytest
```

---

## 8. Docs teknis yang tetap di repo

| File | Isi |
|---|---|
| `shared/docs/API_CONTRACT.md` | Kontrak API bot ↔ Laravel |
| `shared/docs/DEPLOYMENT.md` | Deploy |
| `shared/database/schema.sql` | Skema DB |
| `shared/docs/PORTAL_FILEMAP.md` | File ini |

Folder `docs/` (brief klien, PDF sensitif) **sengaja di-ignore** — jangan commit ulang.

---

## 9. Env penting (jangan commit)

| App | File | Key contoh |
|---|---|---|
| Laravel | `apps/admin-laravel/.env` | `ANTHROPIC_API_KEY`, `PORTAL_AI_MODELS`, DB, Pivot |
| Bot | `apps/bot-python/.env` | `ANTHROPIC_API_KEY`, `CLAUDE_MODELS`, `LARAVEL` API URL, Telegram token |
