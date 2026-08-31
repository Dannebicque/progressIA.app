SHELL := /bin/sh

DEV_COMPOSE := docker compose -f compose.yaml
APP_VERSION ?= latest
PROD_COMPOSE := APP_VERSION=$(APP_VERSION) docker compose -f compose.prod.yaml

.DEFAULT_GOAL := help

.PHONY: help \
	dev-up dev-down dev-restart dev-build dev-logs dev-ps dev-shell dev-console \
	dev-migrate dev-migration-status dev-cache-clear dev-jwt dev-db \
	prod-pull prod-up prod-down prod-restart prod-logs prod-ps prod-shell prod-console \
	prod-migrate prod-migration-status prod-cache-clear prod-cache-warmup prod-jwt \
	prod-db-version prod-doctrine-version

help:
	@printf '%s\n' \
		'ProgressIA - commandes Docker' \
		'' \
		'DEV' \
		'  make dev-up                         Lance la stack de développement' \
		'  make dev-down                       Arrête la stack de développement' \
		'  make dev-restart                    Redémarre la stack' \
		'  make dev-build                      Reconstruit les images' \
		'  make dev-logs                       Suit les logs' \
		'  make dev-ps                         Liste les conteneurs' \
		'  make dev-shell                      Ouvre un shell dans le conteneur PHP' \
		'  make dev-console CMD="about"        Exécute une commande Symfony' \
		'  make dev-migrate                    Exécute les migrations' \
		'  make dev-migration-status           Affiche le statut des migrations' \
		'  make dev-cache-clear                Vide le cache Symfony' \
		'  make dev-jwt                        Génère les clés JWT si nécessaire' \
		'  make dev-db                         Ouvre la CLI MariaDB locale' \
		'' \
		'PROD' \
		'  make prod-pull                      Télécharge les images de production' \
		'  make prod-up                        Démarre/met à jour la production' \
		'  make prod-down                      Arrête la production' \
		'  make prod-restart                   Redémarre les conteneurs' \
		'  make prod-logs                      Suit les logs' \
		'  make prod-ps                        Liste les conteneurs' \
		'  make prod-shell                     Ouvre un shell dans le conteneur PHP' \
		'  make prod-console CMD="about"       Exécute une commande Symfony' \
		'  make prod-migrate                   Exécute les migrations' \
		'  make prod-migration-status          Affiche le statut des migrations' \
		'  make prod-cache-clear               Vide le cache Symfony' \
		'  make prod-cache-warmup              Préchauffe le cache Symfony' \
		'  make prod-jwt                       Génère les clés JWT si nécessaire' \
		'  make prod-db-version                Affiche la version réelle de MariaDB' \
		'  make prod-doctrine-version          Affiche les versions Doctrine' \
		'' \
		'APP_VERSION vaut "latest" par défaut. Exemple : make prod-up APP_VERSION=<sha>'

dev-up:
	$(DEV_COMPOSE) up -d

dev-down:
	$(DEV_COMPOSE) down

dev-restart:
	$(DEV_COMPOSE) restart

dev-build:
	$(DEV_COMPOSE) build

dev-logs:
	$(DEV_COMPOSE) logs -f --tail=200

dev-ps:
	$(DEV_COMPOSE) ps

dev-shell:
	$(DEV_COMPOSE) exec php sh

dev-console:
	$(DEV_COMPOSE) exec php php bin/console $(CMD)

dev-migrate:
	$(DEV_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

dev-migration-status:
	$(DEV_COMPOSE) exec php php bin/console doctrine:migrations:status

dev-cache-clear:
	$(DEV_COMPOSE) exec php php bin/console cache:clear

dev-jwt:
	$(DEV_COMPOSE) exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists

dev-db:
	$(DEV_COMPOSE) exec database mariadb -uprogressia -pprogressia progressia

prod-pull:
	$(PROD_COMPOSE) pull

prod-up:
	$(PROD_COMPOSE) up -d --remove-orphans

prod-down:
	$(PROD_COMPOSE) down

prod-restart:
	$(PROD_COMPOSE) restart

prod-logs:
	$(PROD_COMPOSE) logs -f --tail=200

prod-ps:
	$(PROD_COMPOSE) ps

prod-shell:
	$(PROD_COMPOSE) exec php sh

prod-console:
	$(PROD_COMPOSE) exec php php bin/console $(CMD)

prod-migrate:
	$(PROD_COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

prod-migration-status:
	$(PROD_COMPOSE) exec php php bin/console doctrine:migrations:status

prod-cache-clear:
	$(PROD_COMPOSE) exec php php bin/console cache:clear --env=prod

prod-cache-warmup:
	$(PROD_COMPOSE) exec php php bin/console cache:warmup --env=prod

prod-jwt:
	$(PROD_COMPOSE) exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists

prod-db-version:
	$(PROD_COMPOSE) exec php php -r '$$u=parse_url(getenv("DATABASE_URL")); $$pdo=new PDO("mysql:host=".$$u["host"].";port=".($$u["port"] ?? 3306).";dbname=".ltrim($$u["path"], "/"), $$u["user"], $$u["pass"]); echo "Database version: ".$$pdo->query("SELECT VERSION()")->fetchColumn().PHP_EOL;'

prod-doctrine-version:
	$(PROD_COMPOSE) exec php sh -lc 'composer show doctrine/dbal; composer show doctrine/migrations; composer show doctrine/orm'
