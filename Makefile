.DEFAULT_GOAL := help
SAIL = vendor/bin/sail

.PHONY: help up down restart ide migrate fresh test pint wayfinder

help: ## Muestra este menú
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

## ─── Sail ────────────────────────────────────────────────────────────────────

up: ## Levanta los contenedores en background
	$(SAIL) up -d

down: ## Baja los contenedores
	$(SAIL) down

restart: down up ## Reinicia los contenedores

## ─── IDE helpers ─────────────────────────────────────────────────────────────

ide: ## Regenera todos los helpers del IDE (facades + modelos + meta)
	$(SAIL) artisan ide-helper:generate
	$(SAIL) artisan ide-helper:models --nowrite
	$(SAIL) artisan ide-helper:meta

## ─── Base de datos ───────────────────────────────────────────────────────────

migrate: ## Corre migraciones y regenera helpers del IDE
	$(SAIL) artisan migrate
	@$(MAKE) ide --no-print-directory

fresh: ## Borra todo y recrea la BD con seeders
	$(SAIL) artisan migrate:fresh --seed
	@$(MAKE) ide --no-print-directory

## ─── Código ──────────────────────────────────────────────────────────────────

pint: ## Formatea el código PHP con Pint
	$(SAIL) bin pint --dirty --format agent

wayfinder: ## Regenera los tipos TypeScript de Wayfinder
	$(SAIL) artisan wayfinder:generate

test: ## Corre los tests con Pest
	$(SAIL) artisan test --compact
