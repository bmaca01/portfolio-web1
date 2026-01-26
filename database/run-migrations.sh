#!/bin/bash
# Database Migration Runner
# Runs all SQL migrations in the migrations/ directory
#
# Usage:
#   Development: ./run-migrations.sh dev
#   Production:  ./run-migrations.sh prod
#
# All migrations use IF NOT EXISTS / IF EXISTS clauses to be idempotent

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MIGRATIONS_DIR="$SCRIPT_DIR/migrations"

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

if [ "$1" = "dev" ]; then
    echo -e "${YELLOW}Running migrations on DEVELOPMENT database...${NC}"
    MYSQL_CMD="docker exec -i web1-mysql-dev mysql -u guestbook_user -pguestbook_dev_pass guestbook"
elif [ "$1" = "prod" ]; then
    echo -e "${RED}Running migrations on PRODUCTION database...${NC}"
    echo -e "${YELLOW}Make sure you have SSH access to the production server.${NC}"

    # For production, you would SSH into the server and run mysql
    # Modify this section based on your production setup
    if [ -z "$PROD_DB_HOST" ] || [ -z "$PROD_DB_USER" ] || [ -z "$PROD_DB_PASS" ] || [ -z "$PROD_DB_NAME" ]; then
        echo -e "${RED}Error: Production database credentials not set.${NC}"
        echo "Set these environment variables:"
        echo "  PROD_DB_HOST, PROD_DB_USER, PROD_DB_PASS, PROD_DB_NAME"
        exit 1
    fi
    MYSQL_CMD="mysql -h $PROD_DB_HOST -u $PROD_DB_USER -p$PROD_DB_PASS $PROD_DB_NAME"
else
    echo "Usage: $0 [dev|prod]"
    echo ""
    echo "  dev  - Run migrations on development Docker database"
    echo "  prod - Run migrations on production database (requires env vars)"
    exit 1
fi

# Run each migration file in order
echo ""
for migration in "$MIGRATIONS_DIR"/*.sql; do
    if [ -f "$migration" ]; then
        filename=$(basename "$migration")
        echo -e "${YELLOW}Running: $filename${NC}"

        if $MYSQL_CMD < "$migration" 2>&1; then
            echo -e "${GREEN}  Success${NC}"
        else
            echo -e "${RED}  Failed!${NC}"
            exit 1
        fi
    fi
done

echo ""
echo -e "${GREEN}All migrations completed successfully!${NC}"
