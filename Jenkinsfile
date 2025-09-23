pipeline {
    agent any

    environment {
        GIT_REPO     = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH   = "main"
        DEPLOY_DIR   = "/var/www/html/ecoactivities"
        PHP_BIN      = "php"
        COMPOSER_BIN = "composer"
        DATABASE_URL = "mysql://eco_user:motdepassefort@127.0.0.1:3306/ecoactivitiesdb"
    }

    stages {
        stage('Cloner le repo Git') {
            steps {
                echo " Récupération du code depuis GitHub..."
                git branch: "${GIT_BRANCH}", url: "${GIT_REPO}"
            }
        }
    }

    post {
        success {
            echo ' Clonage terminé avec succès !'
        }
        failure {
            echo ' Le clonage a échoué.'
        }
    }
}
