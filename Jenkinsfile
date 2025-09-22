pipeline {
    agent any

    environment {
        GIT_REPO    = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH  = "main"
        DEPLOY_DIR  = "/var/www/html/ecoactivities"  // répertoire Apache
        PHP_BIN     = "php"
        COMPOSER_BIN = "composer"
        DATABASE_URL = "mysql://eco_user:motdepassefort@127.0.0.1:3306/ecoactivitiesdb"
    }
    stages {
        stage('Cloner le dépôt') {
            steps {
                sh "rm -rf ${DEPLOY_DIR}" 
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${DEPLOY_DIR}"
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
                }
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                script {
                    def envLocal = """
                    APP_ENV=prod
                    APP_DEBUG=0
                    DATABASE_URL=${DATABASE_URL}
                    """.stripIndent()

                    writeFile file: "${DEPLOY_DIR}/.env.local", text: envLocal
                }
            }
        }

        stage('Migration de la base de données') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh "${PHP_BIN} bin/console doctrine:database:create --if-not-exists --env=prod"
                    sh "${PHP_BIN} bin/console doctrine:migrations:migrate --no-interaction --env=prod"
                }
            }
        }

        stage('Exécution des tests') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh "${PHP_BIN} bin/phpunit --testdox"
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${DEPLOY_DIR}") {
                    sh "${PHP_BIN} bin/console cache:clear --env=prod" 
                    sh "${PHP_BIN} bin/console cache:warmup" 
                }
            }
        }

        stage('Déploiement') {
            steps {
                sh "rm -rf /var/www/html/${DEPLOY_DIR}" 
                sh "mkdir -p /var/www/html/${DEPLOY_DIR}" 
                sh "cp -rT ${DEPLOY_DIR} /var/www/html/${DEPLOY_DIR}"
                sh "chown -R www-data:www-data /var/www/html/${DEPLOY_DIR}"
                sh "chmod -R 775 /var/www/html/${DEPLOY_DIR}/var"
            }
        }
    }

    post {
        success {
            echo 'Déploiement réussi !'
        }
        failure {
            echo 'Erreur lors du déploiement.'
        }
    }
}