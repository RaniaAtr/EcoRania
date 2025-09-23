pipeline {
    agent any

    environment {
        GIT_REPO   = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH = "main"
        TEMP_DIR   = "${WORKSPACE}/ecoactivities"
        DEPLOY_DIR = "/var/www/html/ecoactivities"
        COMPOSER_BIN = "composer"
    }

    stages {
        stage('Cloner le dépôt dans le dossier temporaire') {
            steps {
                echo "🔄 Suppression de l’ancien dossier temporaire et clonage..."
                sh "rm -rf ${TEMP_DIR}"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${TEMP_DIR}"
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${TEMP_DIR}") {
                echo " Installation des dépendances PHP..."
                sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
        }
            }
        }
    }

    post {
        success {
            echo 'Étapes temporaires terminées avec succès'
        }
        failure {
            echo 'Le pipeline a échoué'
        }
    }
}
