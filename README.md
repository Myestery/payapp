# Wallet Management API

Welcome to the Wallet Management API. This application, built on Laravel, provides a robust solution for creating and managing wallets, transferring funds, and integrating with payment providers. Below you will find detailed instructions on how to set up, run, and test the application, along with API documentation and an architecture diagram.

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Running the Application](#running-the-application)
5. [Wallet Implementation](#wallet-implementation)
6. [API Documentation](#api-documentation)
7. [Testing](#testing)
8. [Architecture](#architecture)
9. [Contributing](#contributing)
10. [License](#license)

## Features

- Create and manage wallets
- Deposit, withdraw, and check balance
- View transaction history
- Transfer funds between wallets
- Handle wallet-related events (e.g., low balance notifications)
- Integration with Paystack and Flutterwave for transaction processing
- Robust error handling and security measures
- Scalable design to handle numerous wallets and transactions

## Requirements

- PHP >= 8.0
- Laravel >= 9.0
- MySQL or PostgreSQL
- Composer
- Paystack and Flutterwave API credentials

## Installation

1. **Clone the repository:**
    ```sh
    git clone https://github.com/myestery/payapp.git
    cd payapp
    ```

2. **Install dependencies:**
    ```sh
    composer install
    ```

3. **Copy the environment file and set up your environment variables:**
    ```sh
    cp .env.example .env
    ```
    Configure the `.env` file with your database and payment provider credentials.

4. **Generate an application key:**
    ```sh
    php artisan key:generate
    ```

5. **Run database migrations:**
    ```sh
    php artisan migrate
    ```

6. **Seed the database (optional):**
    ```sh
    php artisan db:seed
    ```

## Running the Application

1. **Start the local development server:**
    ```sh
    php artisan serve
    ```
    The application will be available at `http://localhost:8000`.

## Environment Variables
Sample environment variables are provided in the `.env.example` file. You can copy this file to `.env` and update the values to match your environment.

```sh
cp .env.example .env
```

## Wallet Implementation
This is done using the accounts table and the wallet service in [app/Wallet/WalletService.php](app/Wallet/WalletService.php). The wallet service provides methods for creating wallets, depositing and withdrawing funds, checking balances, and transferring funds between wallets.

### Concept: Ledgers
A Ledger holds an array of accounts, actions and amounts. It is used to track the movement of funds between accounts. The Ledger class is defined in [app/Wallet/Ledger.php](app/Wallet/Ledger.php).

### How Debits And Credits Are Handled
A Ledger is created holding accounts to be credited and debited, along with the amounts to be transferred. The Ledger is then processed by the WalletService, which updates the account balances accordingly.

### Account types
The account types are defined in the [app/Models/Account.php](app/Models/Account.php) file. The account types are:
- `REGULAR` - A regular account that can be used for transactions.
- `GL` - A general ledger account that is used for tracking system-wide transactions.

GL accounts are not locked during transactions, however they are meant to be reconciled by [app/Console/Commands/RunGLEOD.php](app/Console/Commands/RunGLEOD.php) which runs the General Ledger End of Day process.

## API Documentation

API documentation and samples can be seen [here](https://documenter.getpostman.com/view/10807467/2sA3QmDEVB#a5f5fbf0-b694-4e96-9e5c-ae3181c14c5a).
https://documenter.getpostman.com/view/10807467/2sA3QmDEVB#a5f5fbf0-b694-4e96-9e5c-ae3181c14c5a

Live Version is hosted [here](https://payappp-a90e537ad231.herokuapp.com)
https://payappp-a90e537ad231.herokuapp.com/


## Testing

Testing Can be done on this repo using github actions or pulling and running the tests
1. **Using Github Actions:**
    - Fork the repository
    - Navigate to the Actions tab
    - Click on the latest workflow run
    - Click on the `Run Tests` job to view the test output

2. **Run the test suite:**
    ```sh
    php artisan test
    ```
    This will run all the tests, including those for wallet management and payment provider integration.

    A sample of the test output is shown below:
    ![Test Output](/test-screenshot.png)

## Architecture

![Architecture Diagram](/architecture-diagram.png)

The diagram illustrates the wallet component and its integration with the payment service. It shows the flow of funds between wallets and the interaction with Paystack and Flutterwave for transaction processing.

## Contributing

Contributions are welcome! Please fork the repository and create a pull request with your changes. Ensure your code is well-documented and includes tests for new features.

