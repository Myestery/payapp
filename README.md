# Wallet Management API

Welcome to the Wallet Management API. This application, built on Laravel, provides a robust solution for creating and managing wallets, transferring funds, and integrating with payment providers. Below you will find detailed instructions on how to set up, run, and test the application, along with API documentation and an architecture diagram.

## Table of Contents

1. [Features](#features)
2. [Requirements](#requirements)
3. [Installation](#installation)
4. [Running the Application](#running-the-application)
5. [API Documentation](#api-documentation)
6. [Testing](#testing)
7. [Architecture](#architecture)
8. [Contributing](#contributing)
9. [License](#license)

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
    git clone https://github.com/your-username/wallet-management-api.git
    cd wallet-management-api
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

## API Documentation

API documentation and samples can be seen [here](https://documenter.getpostman.com/view/10807467/2sA3QmDEVB#a5f5fbf0-b694-4e96-9e5c-ae3181c14c5a).
https://documenter.getpostman.com/view/10807467/2sA3QmDEVB#a5f5fbf0-b694-4e96-9e5c-ae3181c14c5a

Live Version is hosted [here](https://payappp-a90e537ad231.herokuapp.com)
https://payappp-a90e537ad231.herokuapp.com/


## Testing

1. **Run the test suite:**
    ```sh
    php artisan test
    ```
    This will run all the tests, including those for wallet management and payment provider integration.

    A sample of the test output is shown below:
    ![Test Output](/test-screenshot.png)

## Architecture

![Architecture Diagram](path/to/architecture-diagram.png)

The diagram illustrates the wallet component and its integration with the payment service. It shows the flow of funds between wallets and the interaction with Paystack and Flutterwave for transaction processing.

## Contributing

Contributions are welcome! Please fork the repository and create a pull request with your changes. Ensure your code is well-documented and includes tests for new features.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
```
