# Madkrapow App

## Project Documentation
- [Flowchart](https://miro.com/app/board/uXjVIa8ZXCA=/?share_link_id=892283884546)
- [Entity Relationship Diagram (ERD)](https://miro.com/app/board/uXjVIaU0u9U=/?share_link_id=764391113002)

## Setting Up Laravel Sail (Docker Environment)

### Initial Setup
```bash
# Start Laravel Sail
./vendor/bin/sail up -d
```

### Troubleshooting and Maintenance

#### Clean Docker Environment (Use with caution)
```bash
# Remove all unused containers, networks, images, and volumes
docker system prune -a --volumes --force
```

#### Rebuild Sail Environment
```bash
# Stop containers and remove volumes
./vendor/bin/sail down --volumes

# Rebuild without using cache
./vendor/bin/sail build --no-cache

# Start containers in detached mode
./vendor/bin/sail up -d
```

#### Fix Corrupted Dockerfile
```bash
# Remove problematic Dockerfile
rm docker/8.2/Dockerfile

# Create directory if it doesn't exist
mkdir -p docker/8.2

# Download official Dockerfile from Laravel repository
curl -s https://raw.githubusercontent.com/laravel/sail/main/stubs/8.2/Dockerfile -o docker/8.2/Dockerfile
```

#### Resolve MySQL Connection Issues
```bash
# Access the MySQL container
./vendor/bin/sail exec mysql bash

# Connect to MySQL (default password is 'password' unless changed in .env)
mysql -u root -p

# Execute these SQL commands to fix authentication and permissions
ALTER USER 'sail'@'%' IDENTIFIED WITH mysql_native_password BY 'password';
GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%';
FLUSH PRIVILEGES;
EXIT;

# Exit the container
exit

# Restart Sail services
./vendor/bin/sail down && ./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate
```

## Git Commands

### Branch Management
```bash
# Check current branch
git branch

# Rename local branch from 'master' to 'main'
git branch -m main

# Push with upstream tracking
git push -u origin main
```

### Committing Changes
```bash
# Stage all changes
git add -A

# Commit with descriptive message
git commit -m "Your commit message"

# Push to remote repository
git push
```

## Common Issues

- **"Nothing to commit" error**: Make sure you've made actual file changes and staged them properly using `git add`.
- **"Resource still in use" error**: This typically means some containers are still using the network. You can usually proceed with rebuilding, as Sail will handle this automatically.