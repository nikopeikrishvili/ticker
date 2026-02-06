# Ticker

A notebook-style todo and time tracking application with weekly planning features.

## Features

- 📝 **Daily Todos** - Manage tasks for each day
- ⏰ **Time Logging** - Track time with start/end times and descriptions
- 📅 **Weekly Planner** - Plan your week with drag-and-drop task assignment
- 🔄 **Recurring Tasks** - Set up daily or weekly recurring tasks
- 🎨 **Customizable Appearance** - Change colors and dot opacity
- ⌨️ **Keyboard Shortcuts** - Fast navigation with keyboard

## Quick Start with Docker

### Prerequisites

- Docker and Docker Compose installed
- Git (optional)

### Installation

1. **Clone the repository** (or download the release):
   ```bash
   git clone https://github.com/yourusername/ticker.git
   cd ticker
   ```

2. **Create environment file**:
   ```bash
   cp .env.docker .env
   ```

3. **Edit `.env`** and change the passwords:
   ```env
   DB_PASSWORD=your-secure-password
   DB_ROOT_PASSWORD=your-secure-root-password
   ```

4. **Start the application**:
   ```bash
   docker-compose up -d
   ```

5. **Access the application**:
   Open http://localhost:8080 in your browser

### Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_PORT` | 8080 | Port to expose the application |
| `APP_URL` | http://localhost:8080 | Application URL |
| `DB_PASSWORD` | changeme | MySQL user password |
| `DB_ROOT_PASSWORD` | rootchangeme | MySQL root password |

### Commands

```bash
# Start the application
docker-compose up -d

# Stop the application
docker-compose down

# View logs
docker-compose logs -f app

# Run migrations manually
docker-compose exec app php artisan migrate

# Generate recurring todos manually
docker-compose exec app php artisan todos:generate-recurring

# Rebuild after updates
docker-compose build --no-cache
docker-compose up -d
```

## Keyboard Shortcuts

### Notebook Page (/)

| Key | Action |
|-----|--------|
| ← / → | Navigate between days |
| T | Go to today |
| A | Add new todo |
| S | Add new time log |
| R | Recurring tasks |
| W | Weekly planner |
| , | Settings |

### Weekly Planner (/weekly)

| Key | Action |
|-----|--------|
| Alt + ← / Alt + → | Navigate weeks |
| C | Current week |
| M | Move tasks to next week |
| W | Back to notebook |
| , | Settings |

## Development

### Local Setup (without Docker)

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Create environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Run migrations:
   ```bash
   php artisan migrate
   ```

4. Start development servers:
   ```bash
   # Terminal 1
   php artisan serve

   # Terminal 2
   npm run dev
   ```

### Building for Production

```bash
npm run build
```

## Recurring Tasks Scheduler

Recurring tasks are automatically generated at noon (12:00) every day. The Docker setup includes a scheduler container that runs this automatically.

To run manually:
```bash
docker-compose exec app php artisan todos:generate-recurring
```

## License

MIT License
