# SLIC Web Site Services Layer

    We recommend that you open this README in another tab as you perform the tasks below.

    Step 1 : Need to create .env then you should copy constants from .env.local file.

    Step 2 : Need to create database as mentioned in the .env file.

    Setp 3 : To run the command

            composer install

# Required Versions

    Php - >= 8.0

    Lumen - 10x

    Mysql - 8

# To run Fresh Migration

php artisan migrate:fresh

# To run Mysql Dumps

Import Sqls from database/dumps

mysql -u User_Name -p Database_Name < hj_master_company.sql
mysql -u User_Name -p Database_Name < hj_master_industry_type.sql
mysql -u User_Name -p Database_Name < hj_master_pincode.sql
mysql -u User_Name -p Database_Name < hj_master_project.sql
mysql -u User_Name -p Database_Name < hj_master_ifsc.sql

# For Table Creation

php artisan migrate --path=/database/migrations/housing-journey
php artisan migrate --path=/database/migrations/crm
php artisan migrate --path=/database/migrations/service

# For Table Feed

php artisan migrate --seed

# To run Master Data Cron

Need to change future time in UpsertMasterData:Records kernel
Then Run, php artisan schedule:run

# Mongodb Indexing

Collection Name : otp_log

> > db.otp_log.createIndex({ created_at:1 }, { sparse : true })
> > db.otp_log.createIndex({ api_source :1 }, { sparse : true })
> > db.otp_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.otp_log.createIndex({ api_type:1 }, { sparse : true })
> > db.otp_log.createIndex({ api_request_type:1 }, { sparse : true })
> > db.otp_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.otp_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.otp_log.createIndex({ master_product_id:1 }, { sparse : true })
> > db.otp_log.createIndex({ is_otp_sent:1 }, { sparse : true })

Collection Name : pan_log

> > db.pan_log.createIndex({ created_at:1 }, { sparse : true })
> > db.pan_log.createIndex({ api_source :1 }, { sparse : true })
> > db.pan_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.pan_log.createIndex({ api_type:1 }, { sparse : true })
> > db.pan_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.pan_log.createIndex({ mobile_number:1 }, { sparse : true })

Collection Name : cc_push_log

> > db.cc_push_log.createIndex({ created_at:1 }, { sparse : true })
> > db.cc_push_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.cc_push_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.cc_push_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.cc_push_log.createIndex({ lead_id:1 }, { sparse : true })
> > db.cc_push_log.createIndex({ cc_quote_id:1 }, { sparse : true })

Collection Name : bre_log

> > db.bre_log.createIndex({ created_at:1 }, { sparse : true })
> > db.bre_log.createIndex({ api_source :1 }, { sparse : true })
> > db.bre_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.bre_log.createIndex({ api_type:1 }, { sparse : true })
> > db.bre_log.createIndex({ api_request_type:1 }, { sparse : true })
> > db.bre_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.bre_log.createIndex({ quote_id:1 }, { sparse : true })

Collection Name : karza_log

> > db.karza_log.createIndex({ created_at:1 }, { sparse : true })
> > db.karza_log.createIndex({ api_source :1 }, { sparse : true })
> > db.karza_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.karza_log.createIndex({ api_type:1 }, { sparse : true })
> > db.karza_log.createIndex({ pan:1 }, { sparse : true })
> > db.karza_log.createIndex({ api_request_type:1 }, { sparse : true })
> > db.karza_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.karza_log.createIndex({ api_status_code:1 }, { sparse : true })

> > Collection Name : cibil_log

> > db.cibil_log.createIndex({ created_at:1 }, { sparse : true })
> > db.cibil_log.createIndex({ api_source :1 }, { sparse : true })
> > db.cibil_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.cibil_log.createIndex({ api_type:1 }, { sparse : true })
> > db.cibil_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.cibil_log.createIndex({ pan:1 }, { sparse : true })
> > db.cibil_log.createIndex({ api_request_type:1 }, { sparse : true })

Collection Name : api_log

> > db.api_log.createIndex({ created_at:1 }, { sparse : true })
> > db.api_log.createIndex({ api_source :1 }, { sparse : true })
> > db.api_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.api_log.createIndex({ api_type:1 }, { sparse : true })

Collection Name : lead_acquisition_log

> > db.lead_acquisition_log.createIndex({ created_at:1 }, { sparse : true })
> > db.lead_acquisition_log.createIndex({ api_source :1 }, { sparse : true })
> > db.lead_acquisition_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.lead_acquisition_log.createIndex({ api_type:1 }, { sparse : true })
> > db.lead_acquisition_log.createIndex({ utm_source:1 }, { sparse : true })

Collection Name : payment_log

> > db.payment_log.createIndex({ created_at:1 }, { sparse : true })
> > db.payment_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.payment_log.createIndex({ quote_id:1 }, { sparse : true })
> > db.payment_log.createIndex({ payment_transaction_id:1 }, { unique : true })
> > db.payment_log.createIndex({ lead_id:1 }, { sparse : true })

Collection Name : final_submit_log

> > db.final_submit_log.createIndex({ created_at:1 }, { sparse : true })
> > db.final_submit_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.final_submit_log.createIndex({ quote_id:1 }, { sparse : true })

Collection Name : cc_disposition_log

> > db.cc_disposition_log.createIndex({ created_at:1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ api_source :1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ api_type:1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ api_request_type:1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ MobileNo:1 }, { sparse : true })
> > db.cc_disposition_log.createIndex({ LeadID:1 }, { sparse : true })

Collection Name : field_tracking_log

> > db.field_tracking_log.createIndex({ created_at:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ quote_id :1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_quote_id:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ master_product_id:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_push_status:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_push_tag:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_push_stage_id:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_push_sub_stage_id:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.field_tracking_log.createIndex({ cc_push_sub_stage_priority:1 }, { sparse : true })

Collection Name : user_session_activity_log

> > db.user_session_activity_log.createIndex({ created_at:1 }, { sparse : true })
> > db.user_session_activity_log.createIndex({ quote_id :1 }, { sparse : true })
> > db.user_session_activity_log.createIndex({ session_id:1 }, { sparse : true })
> > db.user_session_activity_log.createIndex({ browser_id:1 }, { sparse : true })
> > db.user_session_activity_log.createIndex({ source:1 }, { sparse : true })
> > db.user_session_activity_log.createIndex({ mobile_number:1 }, { sparse : true })

Collection Name : user_portfolio_log

> > db.user_portfolio_log.createIndex({ created_at:1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ quote_id :1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ session_id:1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ browser_id:1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ source:1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ mobile_number:1 }, { sparse : true })
> > db.user_portfolio_log.createIndex({ pan:1 }, { sparse : true })

Collection Name : talisma_create_contact_log

> > db.talisma_create_contact_log.createIndex({ created_at:1 }, { sparse : true })
> > db.talisma_create_contact_log.createIndex({ mobile_number :1 }, { sparse : true })
> > db.talisma_create_contact_log.createIndex({ api_source:1 }, { sparse : true })
> > db.talisma_create_contact_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.talisma_create_contact_log.createIndex({ api_type:1 }, { sparse : true })

Collection Name : talisma_resolve_contact_log

> > db.talisma_resolve_contact_log.createIndex({ created_at:1 }, { sparse : true })
> > db.talisma_resolve_contact_log.createIndex({ mobile_number :1 }, { sparse : true })
> > db.talisma_resolve_contact_log.createIndex({ api_source:1 }, { sparse : true })
> > db.talisma_resolve_contact_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.talisma_resolve_contact_log.createIndex({ api_type:1 }, { sparse : true })

Collection Name : sms_log

> > db.sms_log.createIndex({ created_at:1 }, { sparse : true })
> > db.sms_log.createIndex({ mobile_number :1 }, { sparse : true })
> > db.sms_log.createIndex({ api_source:1 }, { sparse : true })
> > db.sms_log.createIndex({ api_source_page:1 }, { sparse : true })
> > db.sms_log.createIndex({ api_type:1 }, { sparse : true })
