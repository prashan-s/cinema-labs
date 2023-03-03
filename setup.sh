#!/bin/bash

# Setup script for Cinemalabs Educational Security Lab

echo "🎬 Setting up Cinemalabs Educational Security Lab..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    echo "✅ Created .env file. Please update it with your TMDB API credentials if needed."
fi

# Create logs directory
echo "📁 Creating logs directory..."
mkdir -p logs
chmod 777 logs

# Install Composer dependencies
echo "📦 Installing PHP dependencies..."
if command -v composer &> /dev/null; then
    composer install
else
    echo "⚠️  Composer not found locally. Dependencies will be installed in Docker container."
fi

# Build and start Docker containers
echo "🐳 Building and starting Docker containers..."
docker-compose up -d --build

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 15

# Run database migration
echo "🗄️  Running database migration..."
docker-compose exec web php database/migrate.php

echo ""
echo "🎉 Setup complete!"
echo ""
echo "🌐 Application: http://localhost:8000"
echo "📊 phpMyAdmin: http://localhost:8080"
echo ""
echo "Default credentials:"
echo "👤 Admin: admin / password"
echo "👤 Student: student / password"
echo "👤 Test User: testuser / password"
echo ""
echo "⚠️  IMPORTANT: This application contains intentional vulnerabilities for educational purposes only."
echo "   Do not expose this application to the public internet!"
echo ""
echo "📚 To stop the application: docker-compose down"
echo "🔄 To restart: docker-compose up -d"
echo "🗄️  To reset database: docker-compose exec web php database/migrate.php"