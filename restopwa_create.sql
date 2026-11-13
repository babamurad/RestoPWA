-- --------------------------------------------------------
-- Хост:                         PostgreSQL-18
-- Версия сервера:               PostgreSQL 18.1 on x86_64-windows, compiled by msvc-19.44.35221, 64-bit
-- Операционная система:         
-- HeidiSQL Версия:              12.16.0.7229
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES  */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Дамп структуры для таблица public.cache
DROP TABLE IF EXISTS "cache";
CREATE TABLE IF NOT EXISTS "cache" (
	"key" VARCHAR(255) NOT NULL,
	"value" TEXT NOT NULL,
	"expiration" BIGINT NOT NULL,
	PRIMARY KEY ("key")
)
CREATE INDEX "cache_expiration_index" ON "" ("expiration");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.cache_locks
DROP TABLE IF EXISTS "cache_locks";
CREATE TABLE IF NOT EXISTS "cache_locks" (
	"key" VARCHAR(255) NOT NULL,
	"owner" VARCHAR(255) NOT NULL,
	"expiration" BIGINT NOT NULL,
	PRIMARY KEY ("key")
)
CREATE INDEX "cache_locks_expiration_index" ON "" ("expiration");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.categories
DROP TABLE IF EXISTS "categories";
CREATE TABLE IF NOT EXISTS "categories" (
	"id" SERIAL NOT NULL,
	"vendor_id" UUID NOT NULL,
	"parent_id" BIGINT NULL DEFAULT NULL,
	"name" VARCHAR(255) NOT NULL,
	"sort_order" INTEGER NOT NULL DEFAULT 0,
	"is_active" BOOLEAN NOT NULL DEFAULT true,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "categories_parent_id_foreign" FOREIGN KEY ("parent_id") REFERENCES "categories" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "categories_vendor_id_foreign" FOREIGN KEY ("vendor_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
)
CREATE INDEX "categories_vendor_id_index" ON "" ("vendor_id");
CREATE INDEX "categories_parent_id_index" ON "" ("parent_id");
CREATE INDEX "categories_sort_order_index" ON "" ("sort_order");
CREATE INDEX "categories_vendor_id_is_active_index" ON "" ("vendor_id", "is_active");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.courier_earnings
DROP TABLE IF EXISTS "courier_earnings";
CREATE TABLE IF NOT EXISTS "courier_earnings" (
	"id" UUID NOT NULL,
	"order_id" UUID NOT NULL,
	"courier_id" UUID NOT NULL,
	"amount" NUMERIC(10,2) NOT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'pending',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	UNIQUE ("order_id", "courier_id"),
	PRIMARY KEY ("id"),
	CONSTRAINT "courier_earnings_courier_id_foreign" FOREIGN KEY ("courier_id") REFERENCES "courier_profiles" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "courier_earnings_order_id_foreign" FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.courier_offers
DROP TABLE IF EXISTS "courier_offers";
CREATE TABLE IF NOT EXISTS "courier_offers" (
	"id" UUID NOT NULL,
	"order_id" UUID NOT NULL,
	"courier_id" UUID NOT NULL,
	"status" VARCHAR(16) NOT NULL,
	"expires_at" TIMESTAMP NOT NULL,
	"responded_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "courier_offers_courier_id_foreign" FOREIGN KEY ("courier_id") REFERENCES "courier_profiles" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "courier_offers_order_id_foreign" FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "courier_offers_order_id_status_index" ON "" ("order_id", "status");
CREATE INDEX "courier_offers_courier_id_status_index" ON "" ("courier_id", "status");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.courier_profiles
DROP TABLE IF EXISTS "courier_profiles";
CREATE TABLE IF NOT EXISTS "courier_profiles" (
	"id" UUID NOT NULL,
	"user_id" UUID NOT NULL,
	"vendor_id" UUID NULL DEFAULT NULL,
	"vehicle_type" VARCHAR(255) NOT NULL DEFAULT 'walking',
	"status" VARCHAR(255) NOT NULL DEFAULT 'offline',
	"current_lat" NUMERIC(10,8) NULL DEFAULT NULL,
	"current_lon" NUMERIC(11,8) NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"last_assigned_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("user_id"),
	CONSTRAINT "courier_profiles_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "courier_profiles_vendor_id_foreign" FOREIGN KEY ("vendor_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE SET NULL
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.courier_restaurant
DROP TABLE IF EXISTS "courier_restaurant";
CREATE TABLE IF NOT EXISTS "courier_restaurant" (
	"courier_id" UUID NOT NULL,
	"restaurant_id" UUID NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("courier_id", "restaurant_id"),
	CONSTRAINT "courier_restaurant_courier_id_foreign" FOREIGN KEY ("courier_id") REFERENCES "courier_profiles" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "courier_restaurant_restaurant_id_foreign" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.districts
DROP TABLE IF EXISTS "districts";
CREATE TABLE IF NOT EXISTS "districts" (
	"id" UUID NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"city" VARCHAR(255) NULL DEFAULT NULL,
	"boundary" JSON NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT true,
	"sort_order" INTEGER NOT NULL DEFAULT 0,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.failed_jobs
DROP TABLE IF EXISTS "failed_jobs";
CREATE TABLE IF NOT EXISTS "failed_jobs" (
	"id" SERIAL NOT NULL,
	"uuid" VARCHAR(255) NOT NULL,
	"connection" TEXT NOT NULL,
	"queue" TEXT NOT NULL,
	"payload" TEXT NOT NULL,
	"exception" TEXT NOT NULL,
	"failed_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY ("id"),
	UNIQUE ("uuid")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.geocoding_logs
DROP TABLE IF EXISTS "geocoding_logs";
CREATE TABLE IF NOT EXISTS "geocoding_logs" (
	"id" SERIAL NOT NULL,
	"trace_id" VARCHAR(100) NULL DEFAULT NULL,
	"user_id" VARCHAR(100) NULL DEFAULT NULL,
	"vendor_id" VARCHAR(100) NULL DEFAULT NULL,
	"provider" VARCHAR(50) NULL DEFAULT NULL,
	"query" VARCHAR(500) NULL DEFAULT NULL,
	"lat" NUMERIC(10,7) NULL DEFAULT NULL,
	"lon" NUMERIC(10,7) NULL DEFAULT NULL,
	"status" VARCHAR(50) NOT NULL,
	"error_code" VARCHAR(100) NULL DEFAULT NULL,
	"error_message" TEXT NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id")
)
CREATE INDEX "geocoding_logs_trace_id_index" ON "" ("trace_id");
CREATE INDEX "geocoding_logs_user_id_index" ON "" ("user_id");
CREATE INDEX "geocoding_logs_vendor_id_index" ON "" ("vendor_id");
CREATE INDEX "geocoding_logs_status_index" ON "" ("status");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.job_batches
DROP TABLE IF EXISTS "job_batches";
CREATE TABLE IF NOT EXISTS "job_batches" (
	"id" VARCHAR(255) NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"total_jobs" INTEGER NOT NULL,
	"pending_jobs" INTEGER NOT NULL,
	"failed_jobs" INTEGER NOT NULL,
	"failed_job_ids" TEXT NOT NULL,
	"options" TEXT NULL DEFAULT NULL,
	"cancelled_at" INTEGER NULL DEFAULT NULL,
	"created_at" INTEGER NOT NULL,
	"finished_at" INTEGER NULL DEFAULT NULL,
	PRIMARY KEY ("id")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.jobs
DROP TABLE IF EXISTS "jobs";
CREATE TABLE IF NOT EXISTS "jobs" (
	"id" SERIAL NOT NULL,
	"queue" VARCHAR(255) NOT NULL,
	"payload" TEXT NOT NULL,
	"attempts" SMALLINT NOT NULL,
	"reserved_at" INTEGER NULL DEFAULT NULL,
	"available_at" INTEGER NOT NULL,
	"created_at" INTEGER NOT NULL,
	PRIMARY KEY ("id")
)
CREATE INDEX "jobs_queue_index" ON "" ("queue");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.local_places
DROP TABLE IF EXISTS "local_places";
CREATE TABLE IF NOT EXISTS "local_places" (
	"id" UUID NOT NULL,
	"city" VARCHAR(100) NOT NULL DEFAULT 'Туркменабат',
	"name" VARCHAR(255) NOT NULL,
	"aliases" JSON NULL DEFAULT NULL,
	"lat" NUMERIC(10,7) NOT NULL,
	"lon" NUMERIC(10,7) NOT NULL,
	"type" VARCHAR(50) NOT NULL DEFAULT 'other',
	"popularity" INTEGER NOT NULL DEFAULT 0,
	"is_verified" BOOLEAN NOT NULL DEFAULT false,
	"created_by" VARCHAR(255) NULL DEFAULT NULL,
	"updated_by" VARCHAR(255) NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id")
);
CREATE INDEX "local_places_city_index" ON "" ("city");
CREATE INDEX "local_places_type_index" ON "" ("type");
CREATE INDEX "local_places_popularity_index" ON "" ("popularity");
CREATE INDEX "local_places_is_verified_index" ON "" ("is_verified");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.migrations
DROP TABLE IF EXISTS "migrations";
CREATE TABLE IF NOT EXISTS "migrations" (
	"id" SERIAL NOT NULL,
	"migration" VARCHAR(255) NOT NULL,
	"batch" INTEGER NOT NULL,
	PRIMARY KEY ("id")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.order_rejections
DROP TABLE IF EXISTS "order_rejections";
CREATE TABLE IF NOT EXISTS "order_rejections" (
	"id" SERIAL NOT NULL,
	"reason" VARCHAR(40) NOT NULL,
	"vendor_id" UUID NULL DEFAULT NULL,
	"user_id" UUID NULL DEFAULT NULL,
	"created_at" TIMESTAMP NOT NULL,
	PRIMARY KEY ("id")
)
CREATE INDEX "order_rejections_reason_index" ON "" ("reason");
CREATE INDEX "order_rejections_vendor_id_index" ON "" ("vendor_id");
CREATE INDEX "order_rejections_created_at_index" ON "" ("created_at");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.order_status_history
DROP TABLE IF EXISTS "order_status_history";
CREATE TABLE IF NOT EXISTS "order_status_history" (
	"order_id" UUID NOT NULL,
	"from_status" VARCHAR(20) NULL DEFAULT NULL,
	"to_status" VARCHAR(20) NOT NULL,
	"metadata" JSON NULL DEFAULT NULL,
	"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	"id" UUID NOT NULL DEFAULT gen_random_uuid(),
	PRIMARY KEY ("id"),
	CONSTRAINT "order_status_history_order_id_foreign" FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "order_status_history_order_id_index" ON "" ("order_id");
CREATE INDEX "order_status_history_created_at_index" ON "" ("created_at");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.orders
DROP TABLE IF EXISTS "orders";
CREATE TABLE IF NOT EXISTS "orders" (
	"id" UUID NOT NULL,
	"vendor_id" UUID NOT NULL,
	"user_id" UUID NULL DEFAULT NULL,
	"status" VARCHAR(20) NOT NULL DEFAULT 'pending',
	"address" JSONB NOT NULL,
	"items" JSONB NOT NULL,
	"total" NUMERIC(10,2) NOT NULL,
	"payment_status" VARCHAR(20) NOT NULL DEFAULT 'unpaid',
	"delivery_fee" NUMERIC(10,2) NOT NULL DEFAULT '0',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"delivery_time" VARCHAR(50) NULL DEFAULT NULL,
	"payment_method" VARCHAR(20) NOT NULL DEFAULT 'card',
	"comment" TEXT NULL DEFAULT NULL,
	"is_offline" BOOLEAN NOT NULL DEFAULT false,
	"idempotency_key" VARCHAR(255) NULL DEFAULT NULL,
	"metadata" JSONB NULL DEFAULT NULL,
	"courier_id" UUID NULL DEFAULT NULL,
	"commission_amount" INTEGER NOT NULL DEFAULT 0,
	"vendor_settlement_id" UUID NULL DEFAULT NULL,
	"delivery_type" VARCHAR(16) NOT NULL DEFAULT 'delivery',
	"number" BIGINT NULL DEFAULT NULL,
	UNIQUE ("number"),
	PRIMARY KEY ("id"),
	UNIQUE ("idempotency_key"),
	CONSTRAINT "orders_courier_id_foreign" FOREIGN KEY ("courier_id") REFERENCES "courier_profiles" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "orders_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "orders_vendor_id_foreign" FOREIGN KEY ("vendor_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "orders_vendor_settlement_id_foreign" FOREIGN KEY ("vendor_settlement_id") REFERENCES "vendor_settlements" ("id") ON UPDATE NO ACTION ON DELETE SET NULL
);
CREATE INDEX "orders_payment_status_index" ON "" ("payment_status");
CREATE INDEX "orders_vendor_id_index" ON "" ("vendor_id");
CREATE INDEX "orders_user_id_index" ON "" ("user_id");
CREATE INDEX "orders_status_index" ON "" ("status");
CREATE INDEX "orders_created_at_index" ON "" ("created_at");
CREATE INDEX "orders_vendor_id_status_index" ON "" ("vendor_id", "status");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.password_reset_tokens
DROP TABLE IF EXISTS "password_reset_tokens";
CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
	"email" VARCHAR(255) NOT NULL,
	"token" VARCHAR(255) NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("email")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.payment_transactions
DROP TABLE IF EXISTS "payment_transactions";
CREATE TABLE IF NOT EXISTS "payment_transactions" (
	"id" UUID NOT NULL,
	"order_id" UUID NOT NULL,
	"provider" VARCHAR(32) NOT NULL,
	"provider_transaction_id" VARCHAR(255) NULL DEFAULT NULL,
	"type" VARCHAR(16) NOT NULL,
	"status" VARCHAR(16) NOT NULL,
	"amount" BIGINT NOT NULL,
	"currency" VARCHAR(3) NOT NULL DEFAULT 'TMT',
	"raw_payload" JSON NULL DEFAULT NULL,
	"idempotency_key" VARCHAR(255) NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("idempotency_key"),
	CONSTRAINT "payment_transactions_order_id_foreign" FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "payment_transactions_order_id_type_status_index" ON "" ("order_id", "type", "status");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.personal_access_tokens
DROP TABLE IF EXISTS "personal_access_tokens";
CREATE TABLE IF NOT EXISTS "personal_access_tokens" (
	"id" SERIAL NOT NULL,
	"tokenable_type" VARCHAR(255) NOT NULL,
	"tokenable_id" UUID NOT NULL,
	"name" TEXT NOT NULL,
	"token" VARCHAR(64) NOT NULL,
	"abilities" TEXT NULL DEFAULT NULL,
	"last_used_at" TIMESTAMP NULL DEFAULT NULL,
	"expires_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("token")
)
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" ON "" ("tokenable_type", "tokenable_id");
CREATE INDEX "personal_access_tokens_expires_at_index" ON "" ("expires_at");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.products
DROP TABLE IF EXISTS "products";
CREATE TABLE IF NOT EXISTS "products" (
	"id" UUID NOT NULL,
	"vendor_id" UUID NOT NULL,
	"category_id" BIGINT NULL DEFAULT NULL,
	"name" VARCHAR(255) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"price" NUMERIC(10,2) NOT NULL,
	"modifiers" JSONB NULL DEFAULT NULL,
	"image" VARCHAR(255) NULL DEFAULT NULL,
	"weight_g" INTEGER NULL DEFAULT NULL,
	"kcal" INTEGER NULL DEFAULT NULL,
	"is_available" BOOLEAN NOT NULL DEFAULT true,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "products_category_id_foreign" FOREIGN KEY ("category_id") REFERENCES "categories" ("id") ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT "products_vendor_id_foreign" FOREIGN KEY ("vendor_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "products_vendor_id_index" ON "" ("vendor_id");
CREATE INDEX "products_category_id_index" ON "" ("category_id");
CREATE INDEX "products_is_available_index" ON "" ("is_available");
CREATE INDEX "products_vendor_id_is_available_index" ON "" ("vendor_id", "is_available");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.push_subscriptions
DROP TABLE IF EXISTS "push_subscriptions";
CREATE TABLE IF NOT EXISTS "push_subscriptions" (
	"id" UUID NOT NULL,
	"user_id" UUID NOT NULL,
	"endpoint" VARCHAR(500) NOT NULL,
	"p256dh" VARCHAR(255) NOT NULL,
	"auth" VARCHAR(255) NOT NULL,
	"expires_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("endpoint"),
	CONSTRAINT "push_subscriptions_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "push_subscriptions_user_id_endpoint_index" ON "" ("user_id", "endpoint");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.restaurant_delivery_prices
DROP TABLE IF EXISTS "restaurant_delivery_prices";
CREATE TABLE IF NOT EXISTS "restaurant_delivery_prices" (
	"id" SERIAL NOT NULL,
	"restaurant_id" UUID NOT NULL,
	"district_id" UUID NOT NULL,
	"price" INTEGER NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("restaurant_id", "district_id"),
	CONSTRAINT "restaurant_delivery_prices_district_id_foreign" FOREIGN KEY ("district_id") REFERENCES "districts" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "restaurant_delivery_prices_restaurant_id_foreign" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.restaurant_schedules
DROP TABLE IF EXISTS "restaurant_schedules";
CREATE TABLE IF NOT EXISTS "restaurant_schedules" (
	"id" UUID NOT NULL,
	"restaurant_id" UUID NOT NULL,
	"weekday" SMALLINT NOT NULL,
	"opens_at" TIME NULL DEFAULT NULL,
	"closes_at" TIME NULL DEFAULT NULL,
	"is_closed" BOOLEAN NOT NULL DEFAULT false,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	UNIQUE ("restaurant_id", "weekday"),
	PRIMARY KEY ("id"),
	CONSTRAINT "restaurant_schedules_restaurant_id_foreign" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.restaurants
DROP TABLE IF EXISTS "restaurants";
CREATE TABLE IF NOT EXISTS "restaurants" (
	"id" UUID NOT NULL,
	"slug" VARCHAR(255) NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"description" TEXT NULL DEFAULT NULL,
	"settings" JSONB NULL DEFAULT NULL,
	"is_active" BOOLEAN NOT NULL DEFAULT true,
	"commission_rate" NUMERIC(5,2) NOT NULL DEFAULT '15',
	"owner_id" UUID NOT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"delivery_zones" TEXT NULL DEFAULT NULL,
	"vendor_id" VARCHAR(255) NULL DEFAULT NULL,
	"image" VARCHAR(255) NULL DEFAULT NULL,
	"cover_image" VARCHAR(255) NULL DEFAULT NULL,
	"rating" NUMERIC(3,1) NULL DEFAULT NULL,
	"review_count" INTEGER NOT NULL DEFAULT 0,
	"delivery_time" VARCHAR(255) NULL DEFAULT NULL,
	"delivery_fee" NUMERIC(10,2) NOT NULL DEFAULT '0',
	"min_order" INTEGER NOT NULL DEFAULT 0,
	"is_paused" BOOLEAN NOT NULL DEFAULT false,
	"pause_reason" VARCHAR(255) NULL DEFAULT NULL,
	"timezone" VARCHAR(255) NOT NULL DEFAULT 'Asia/Ashgabat',
	"courier_fixed_fee" NUMERIC(10,2) NOT NULL DEFAULT '0',
	"courier_percent_fee" NUMERIC(5,2) NOT NULL DEFAULT '0',
	"commission_percent" NUMERIC(5,2) NOT NULL DEFAULT '0',
	PRIMARY KEY ("id"),
	UNIQUE ("slug"),
	CONSTRAINT "restaurants_owner_id_foreign" FOREIGN KEY ("owner_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "restaurants_owner_id_index" ON "" ("owner_id");
CREATE INDEX "restaurants_is_active_index" ON "" ("is_active");
CREATE INDEX "restaurants_vendor_id_index" ON "" ("vendor_id");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.reviews
DROP TABLE IF EXISTS "reviews";
CREATE TABLE IF NOT EXISTS "reviews" (
	"id" UUID NOT NULL,
	"order_id" UUID NOT NULL,
	"user_id" UUID NOT NULL,
	"restaurant_id" UUID NOT NULL,
	"rating" SMALLINT NOT NULL,
	"comment" TEXT NULL DEFAULT NULL,
	"vendor_reply" TEXT NULL DEFAULT NULL,
	"replied_at" TIMESTAMP NULL DEFAULT NULL,
	"is_hidden" BOOLEAN NOT NULL DEFAULT false,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	UNIQUE ("order_id"),
	CONSTRAINT "reviews_order_id_foreign" FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "reviews_restaurant_id_foreign" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT "reviews_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);
CREATE INDEX "reviews_restaurant_id_is_hidden_created_at_index" ON "" ("restaurant_id", "is_hidden", "created_at");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.sequences
DROP TABLE IF EXISTS "sequences";
CREATE TABLE IF NOT EXISTS "sequences" (
	"name" VARCHAR(255) NOT NULL,
	"value" BIGINT NOT NULL,
	PRIMARY KEY ("name")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.sessions
DROP TABLE IF EXISTS "sessions";
CREATE TABLE IF NOT EXISTS "sessions" (
	"id" VARCHAR(255) NOT NULL,
	"user_id" UUID NULL DEFAULT NULL,
	"ip_address" VARCHAR(45) NULL DEFAULT NULL,
	"user_agent" TEXT NULL DEFAULT NULL,
	"payload" TEXT NOT NULL,
	"last_activity" INTEGER NOT NULL,
	PRIMARY KEY ("id")
)
CREATE INDEX "sessions_user_id_index" ON "" ("user_id");
CREATE INDEX "sessions_last_activity_index" ON "" ("last_activity");;

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.sms_logs
DROP TABLE IF EXISTS "sms_logs";
CREATE TABLE IF NOT EXISTS "sms_logs" (
	"id" UUID NOT NULL,
	"phone" VARCHAR(255) NOT NULL,
	"message" TEXT NOT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'pending',
	"provider" VARCHAR(255) NULL DEFAULT NULL,
	"error_message" TEXT NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.support_tickets
DROP TABLE IF EXISTS "support_tickets";
CREATE TABLE IF NOT EXISTS "support_tickets" (
	"id" SERIAL NOT NULL,
	"user_id" UUID NOT NULL,
	"subject" VARCHAR(255) NULL DEFAULT NULL,
	"message" TEXT NOT NULL,
	"status" VARCHAR(255) NOT NULL DEFAULT 'open',
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "support_tickets_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.user_addresses
DROP TABLE IF EXISTS "user_addresses";
CREATE TABLE IF NOT EXISTS "user_addresses" (
	"id" SERIAL NOT NULL,
	"user_id" UUID NOT NULL,
	"title" VARCHAR(255) NULL DEFAULT NULL,
	"full_address" VARCHAR(255) NOT NULL,
	"entrance" VARCHAR(255) NULL DEFAULT NULL,
	"floor" VARCHAR(255) NULL DEFAULT NULL,
	"apartment" VARCHAR(255) NULL DEFAULT NULL,
	"intercom" VARCHAR(255) NULL DEFAULT NULL,
	"comment" VARCHAR(255) NULL DEFAULT NULL,
	"lat" NUMERIC(10,8) NULL DEFAULT NULL,
	"lng" NUMERIC(11,8) NULL DEFAULT NULL,
	"is_default" BOOLEAN NOT NULL DEFAULT false,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "user_addresses_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.user_payment_methods
DROP TABLE IF EXISTS "user_payment_methods";
CREATE TABLE IF NOT EXISTS "user_payment_methods" (
	"id" SERIAL NOT NULL,
	"user_id" UUID NOT NULL,
	"card_mask" VARCHAR(255) NOT NULL,
	"card_type" VARCHAR(255) NULL DEFAULT NULL,
	"is_default" BOOLEAN NOT NULL DEFAULT false,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "user_payment_methods_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.users
DROP TABLE IF EXISTS "users";
CREATE TABLE IF NOT EXISTS "users" (
	"id" UUID NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"email" VARCHAR(255) NOT NULL,
	"email_verified_at" TIMESTAMP NULL DEFAULT NULL,
	"password" VARCHAR(255) NULL DEFAULT NULL,
	"remember_token" VARCHAR(100) NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	"phone" VARCHAR(15) NULL DEFAULT NULL,
	"role" VARCHAR(255) NOT NULL DEFAULT 'client',
	"google_id" VARCHAR(255) NULL DEFAULT NULL,
	"avatar" VARCHAR(255) NULL DEFAULT NULL,
	"terms_accepted_at" TIMESTAMP NULL DEFAULT NULL,
	"terms_version" VARCHAR(32) NULL DEFAULT NULL,
	"locale" VARCHAR(5) NOT NULL DEFAULT 'ru',
	"deleted_at" TIMESTAMP NULL DEFAULT NULL,
	UNIQUE ("google_id"),
	PRIMARY KEY ("id"),
	UNIQUE ("email"),
	UNIQUE ("phone")
);

-- Экспортируемые данные не выделены.

-- Дамп структуры для таблица public.vendor_settlements
DROP TABLE IF EXISTS "vendor_settlements";
CREATE TABLE IF NOT EXISTS "vendor_settlements" (
	"id" UUID NOT NULL,
	"restaurant_id" UUID NOT NULL,
	"period_from" DATE NOT NULL,
	"period_to" DATE NOT NULL,
	"gross_amount" INTEGER NOT NULL DEFAULT 0,
	"commission_amount" INTEGER NOT NULL DEFAULT 0,
	"net_payable" INTEGER NOT NULL DEFAULT 0,
	"status" VARCHAR(255) NOT NULL DEFAULT 'draft',
	"paid_at" TIMESTAMP NULL DEFAULT NULL,
	"created_at" TIMESTAMP NULL DEFAULT NULL,
	"updated_at" TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY ("id"),
	CONSTRAINT "vendor_settlements_restaurant_id_foreign" FOREIGN KEY ("restaurant_id") REFERENCES "restaurants" ("id") ON UPDATE NO ACTION ON DELETE CASCADE
);

-- Экспортируемые данные не выделены.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
