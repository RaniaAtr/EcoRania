pipeline {
    agent any

    environment {
        GIT_REPO     = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH   = "main"
        TEMP_DIR     = "${WORKSPACE}/ecoactivities"
        DEPLOY_DIR   = "/var/www/html/ecoactivities"
        COMPOSER_BIN = "composer"
        SSH_OPTS     = "-o StrictHostKeyChecking=no"
        REMOTE_USER  = "ubuntu"
        REMOTE_HOST  = "51.75.207.28"
    }

    stages {
        stage('Cloner le dépôt dans le dossier temporaire') {
            steps {
                echo "🔄 Suppression de l’ancien dossier temporaire et clonage..."
                sh "rm -rf ${TEMP_DIR}"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${TEMP_DIR}"
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                script {
                    def envFile = """
                        APP_ENV=dev
                        APP_DEBUG=1
                        DATABASE_URL="mysql://sonar:sonar@51.75.207.28:3306/ecobase?serverVersion=11&charset=utf8"
                        MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
                        JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
                        JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
                        JWT_PASSPHRASE=Nana2022&
                    """.stripIndent()

                    writeFile file: "${TEMP_DIR}/.env", text: envFile
                    writeFile file: "${TEMP_DIR}/.env.local", text: envFile
                    echo "✅ Fichiers .env et .env.local créés avec succès"
                }
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${TEMP_DIR}") {
                    echo "📦 Installation des dépendances PHP..."
                    sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
                }
            }
        }

        stage('Vérifier PHP') {
            steps {
                sh 'which php'
                sh 'php -v'
                sh 'php -m | grep pdo_mysql || true'
            }
        }

        stage('DB migrate') {
            steps {
                dir("${TEMP_DIR}") {
                    sh '''
                        set -e
                        php bin/console doctrine:database:drop --if-exists --force
                        php bin/console doctrine:database:create --if-not-exists
                        php bin/console doctrine:schema:update --force
                        php bin/console doctrine:schema:validate || echo "⚠️  Validation warning - continuing deployment"
                    '''
    }
  }
}

        stage('Exécution des tests PHP Unit') {
            steps {
                dir("${TEMP_DIR}") {
                    echo " Lancement des tests PHPUnit..."
                    sh './vendor/bin/phpunit --testdox'
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${TEMP_DIR}") {
                    echo "🧹 Nettoyage et réchauffage du cache Symfony..."
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        stage('Déploiement') {
            steps {
                echo "🚀 Déploiement du site en production..."
                sh """
                    # 1. Supprimer tout sauf var/
                     ssh ${SSH_OPTS} ${REMOTE_USER}@${REMOTE_HOST} "find ${DEPLOY_DIR} -mindepth 1 -maxdepth 1 ! -name var -exec rm -rf {} +"

                    # 2. Copier le nouveau contenu
                    scp ${SSH_OPTS} -r "${TEMP_DIR}/." ${REMOTE_USER}@${REMOTE_HOST}:${DEPLOY_DIR}/

                """
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
