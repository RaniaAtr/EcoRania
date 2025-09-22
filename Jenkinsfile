pipeline {
    agent any

    environment {
        GIT_REPO     = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH   = "main"
        DEPLOY_DIR   = "/var/www/html/ecoactivities"  // dossier final pour Apache
        PHP_BIN      = "php"
        COMPOSER_BIN = "composer"
        DATABASE_URL = "mysql://eco_user:motdepassefort@127.0.0.1:3306/ecoactivitiesdb"
    }

    stages {
        stage('Cloner le dépôt') {
            steps {
                // On clone DANS le workspace Jenkins (par défaut)
                sh "rm -rf EcoRania"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} EcoRania"
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("EcoRania") {
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

                    writeFile file: "EcoRania/.env.local", text: envLocal
                }
            }
        }

        stage('Migration de la base de données') {
            steps {
                dir("EcoRania") {
                    sh "${PHP_BIN} bin/console doctrine:database:create --if-not-exists --env=prod"
                    sh "${PHP_BIN} bin/console doctrine:migrations:migrate --no-interaction --env=prod"
                }
            }
        }

        stage('Exécution des tests') {
            steps {
                dir("EcoRania") {
                    sh "${PHP_BIN} bin/phpunit --testdox || true"
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("EcoRania") {
                    sh "${PHP_BIN} bin/console cache:clear --env=prod"
                    sh "${PHP_BIN} bin/console cache:warmup"
                }
            }
        }

        stage('Déploiement') {
            steps {
                // Copie du code préparé vers /var/www/html/ecoactivities
                sh "sudo rm -rf ${DEPLOY_DIR}"
                sh "sudo mkdir -p ${DEPLOY_DIR}"
                sh "sudo cp -rT EcoRania ${DEPLOY_DIR}"
                sh "sudo chown -R www-data:www-data ${DEPLOY_DIR}"
                sh "sudo chmod -R 775 ${DEPLOY_DIR}/var"
            }
        }
    }

    post {
        success {
            echo '✅ Déploiement réussi !'
        }
        failure {
            echo '❌ Erreur lors du déploiement.'
        }
    }
}
