# Database Files

This directory contains database schema and initialization files for the guestbook feature.

## Files

- `schema.sql` - Database schema (table definitions)
- `init-dev.sql` - Sample data for development environment
- `.gitignore` - Excludes credentials and backups from git

## Usage

### Development
The MySQL container automatically loads these files on first startup via docker-compose.yml volume mounts.

### Production
Run the schema manually on the production MySQL container:
```bash
# On production server
docker exec -i web1-mysql mysql -uroot -p < /home/deploy/web1-site1/database/schema.sql
```
