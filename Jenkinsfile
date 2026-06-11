pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    stages {
        stage('Development Validation') {
            when {
                branch 'development'
            }
            steps {
                dir('.') {
                    sh '''
                        composer install --no-interaction --prefer-dist
                        php artisan config:clear
                        php artisan route:clear
                    '''
                }
            }
        }

        stage('Deploy Staging') {
            when {
                branch 'staging'
            }
            steps {
                sh '''
                    set -e
                    cd /var/www/pmtool-medics/staging/pmtool-api-vps

                    git fetch origin
                    git reset --hard origin/staging

                    composer install \
                        --no-interaction \
                        --prefer-dist \
                        --optimize-autoloader

                    php artisan optimize:clear
                    php artisan config:cache
                    php artisan route:cache
                    php artisan view:cache

                    echo "Staging backend deployment completed"
                '''
            }
        }

        stage('Production Approval') {
            when {
                branch 'main'
            }
            steps {
                input(
                    message: 'Deploy Backend to Production?',
                    ok: 'Deploy'
                )
            }
        }

        stage('Deploy Production') {
            when {
                branch 'main'
            }
            steps {
                sh '''
                    set -e
                    cd /var/www/pmtool-medics/pmtool-api-vps

                    git fetch origin
                    git reset --hard origin/main

                    composer install \
                        --no-interaction \
                        --prefer-dist \
                        --optimize-autoloader

                    php artisan optimize:clear
                    php artisan config:cache
                    php artisan route:cache
                    php artisan view:cache

                    echo "Production backend deployment completed"
                '''
            }
        }
    }

    post {
        success {
            echo 'Backend pipeline completed successfully'
        }
        failure {
            echo 'Backend pipeline failed'
        }
    }
}

