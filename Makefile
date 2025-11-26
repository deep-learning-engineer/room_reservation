.PHONY: help install test lint lint-dry fix analysis check start stop down clean deploy dev reset

RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[0;33m
BLUE=\033[0;34m
NC=\033[0m

DOCKER_COMPOSE = docker-compose
PHP_CONTAINER = php
COMPOSER = composer
PHPUNIT = ./bin/phpunit
PHPCS = ./vendor/bin/phpcs
PHPCSFIXER = ./vendor/bin/php-cs-fixer
PSALM = ./vendor/bin/psalm

ENV_DEV = .env.test
ENV_PROD = .env.local

DOCKER_COMPOSE_DEV = $(DOCKER_COMPOSE) --env-file $(ENV_DEV)
DOCKER_COMPOSE_PROD = $(DOCKER_COMPOSE) --env-file $(ENV_PROD)

DOCKER_EXEC_DEV = $(DOCKER_COMPOSE_DEV) exec -T $(PHP_CONTAINER)
DOCKER_EXEC_PROD = $(DOCKER_COMPOSE_PROD) exec -T $(PHP_CONTAINER)

COMPOSER_DEV = $(DOCKER_EXEC_DEV) $(COMPOSER)
PHPUNIT_DEV = $(DOCKER_EXEC_DEV) $(PHPUNIT)
PHPCS_DEV = $(DOCKER_EXEC_DEV) $(PHPCS)
PHPCSFIXER_DEV = $(DOCKER_EXEC_DEV) $(PHPCSFIXER)
PSALM_DEV = $(DOCKER_EXEC_DEV) $(PSALM)

PHPCS_PROD = $(DOCKER_EXEC_PROD) $(PHPCS)
PSALM_PROD = $(DOCKER_EXEC_PROD) $(PSALM)
COMPOSER_PROD = $(DOCKER_EXEC_PROD) $(COMPOSER)

help: ## Show this help
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${GREEN}%-15s${NC} %s\n", $$1, $$2}'

# Service management
start: ## Start all services (development)
	@echo "${BLUE}Starting services...${NC}"
	$(DOCKER_COMPOSE_DEV) up -d --build

start-prod: ## Start all services (production)
	@echo "${BLUE}Starting production services...${NC}"
	$(DOCKER_COMPOSE_PROD) up -d --build

stop: ## Stop all services (development)
	@echo "${YELLOW}Stopping development services...${NC}"
	$(DOCKER_COMPOSE_DEV) stop

stop-prod: ## Stop all services (production)
	@echo "${BLUE}Stopping production services...${NC}"
	$(DOCKER_COMPOSE_PROD) up -d --build

down: ## Stop and remove containers
	@echo "${YELLOW}Stopping and removing containers...${NC}"
	$(DOCKER_COMPOSE_DEV) down -v

restart: stop start ## Restart services

# Dependencies
install: ## Install dependencies (development)
	@echo "${BLUE}Installing Composer dependencies...${NC}"
	$(DOCKER_EXEC_DEV) git config --global --add safe.directory /var/www/html
	$(COMPOSER_DEV) install --no-interaction --prefer-dist --optimize-autoloader

install-prod: ## Install dependencies (production)
	@echo "${BLUE}Installing Composer dependencies for production...${NC}"
	$(DOCKER_EXEC_PROD) git config --global --add safe.directory /var/www/html
	$(COMPOSER_PROD) install --no-interaction --prefer-dist --optimize-autoloader --no-dev

update: ## Update dependencies (development)
	@echo "${BLUE}Updating Composer dependencies...${NC}"
	$(COMPOSER_DEV) update --no-interaction --prefer-dist --optimize-autoloader

# Testing
test: ## Run tests (development)
	@echo "${BLUE}Running tests...${NC}"
	$(PHPUNIT_DEV)

test-coverage: ## Run tests with coverage (development)
	@echo "${BLUE}Running tests with coverage...${NC}"
	$(PHPUNIT_DEV) --coverage-html var/coverage

# Code quality
lint: ## Run code style check (PHP_CodeSniffer)
	@echo "${BLUE}Running code style check...${NC}"
	$(PHPCS_DEV)

lint-dry: ## Run PHP-CS-Fixer dry run (check without fixing)
	@echo "${BLUE}Running PHP-CS-Fixer dry run...${NC}"
	$(PHPCSFIXER_DEV) fix --dry-run --diff

lint-fix: ## Fix code style issues (PHP-CS-Fixer)
	@echo "${BLUE}Fixing code style issues...${NC}"
	$(PHPCSFIXER_DEV) fix --diff

analysis: ## Run static analysis (Psalm)
	@echo "${BLUE}Running static analysis...${NC}"
	$(PSALM_DEV)

security: ## Check for security vulnerabilities
	@echo "${BLUE}Checking for security vulnerabilities...${NC}"
	$(COMPOSER_DEV) audit

# Comprehensive checks
check: lint lint-dry analysis test ## Run all checks (development)

init-test-db: ## Initialize database for testing
	@echo "${BLUE}Initializing test database...${NC}"
	# Drop existing test db if exists (ensures clean slate)
	$(DOCKER_EXEC_DEV) php bin/console doctrine:database:drop --force --env=test --if-exists
	# Create the room_database_test
	$(DOCKER_EXEC_DEV) php bin/console doctrine:database:create --env=test
	# Create tables/schema
	$(DOCKER_EXEC_DEV) php bin/console doctrine:schema:create --env=test

ci: ## Run CI checks (without fixing)
	@echo "${BLUE}Running CI checks...${NC}"
	$(PHPCS_DEV) || (echo "${RED}Code style check (PHP_CodeSniffer) failed${NC}" && exit 1)
	$(PHPCSFIXER_DEV) fix --dry-run --diff || (echo "${RED}Code style check (PHP-CS-Fixer) failed${NC}" && exit 1)
	$(PSALM_DEV) || (echo "${RED}Static analysis failed${NC}" && exit 1)
	make init-test-db
	$(PHPUNIT_DEV) || (echo "${RED}Tests failed${NC}" && exit 1)
	@echo "${GREEN}All CI checks passed!${NC}"

# Deployment
deploy: start-prod install-prod ## Deploy project with production environment
	@echo "${GREEN}Successfully deployed to production!${NC}"

# Development workflow
dev: start install ## Start development environment
	@echo "${GREEN}Development environment ready!${NC}"

# Logs
logs: ## Show all service logs (development)
	$(DOCKER_COMPOSE_DEV) logs -f

logs-prod: ## Show all service logs (production)
	$(DOCKER_COMPOSE_PROD) logs -f
