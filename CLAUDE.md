# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based poker settlement calculator web application that tracks player balances and calculates optimal payment settlements. The application uses PHP sessions for state management and runs in a Docker container with Apache.

## Key Architecture

The application follows a simple MVC-like pattern with three main PHP files:

- **index.php**: Main entry point and view layer - handles UI display of players, balances, and game state
- **logic.php**: Controller layer - processes POST requests and manages session state
- **functions.php**: Model layer - contains core business logic for balance calculations and settlement algorithms

The application maintains game state through PHP sessions where the sum of all player balances must equal zero (zero-sum game).

## Development Commands

### Running the Application Locally

```bash
# Using Docker
docker build -t poker-calc .
docker run -p 8080:80 poker-calc

# Using PHP built-in server (requires PHP 8.2+)
php -S localhost:8080
```

### Docker Build and Deployment

The project uses GitHub Actions for automated Docker builds. Images are published to ghcr.io automatically on push to main branch.

```bash
# Manual Docker build
docker build -t poker-calc .

# Run with port mapping
docker run -p 8080:80 poker-calc
```

## Important Implementation Details

1. **Session Management**: All game state is stored in PHP sessions. The `logic.php` file handles session initialization and state updates.

2. **Balance Validation**: The application enforces that the sum of all balances equals zero. This validation happens in the `controleerSom()` function.

3. **Settlement Algorithm**: The `berekenAfrekening()` function in `functions.php` implements the core algorithm to minimize the number of transactions needed to settle all debts.

4. **Dutch Language**: The UI and some function names use Dutch. Key terms:
   - "speler" = player
   - "saldo" = balance
   - "afrekening" = settlement
   - "bank" = bank/house

5. **No External Dependencies**: The application is self-contained with no composer dependencies or JavaScript frameworks.