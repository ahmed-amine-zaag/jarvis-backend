
pipeline {
    agent any
    
    stages {
        stage('Hello') {
            steps {
                echo 'Hello, World!'
            }
        }
        
        stage('developpement') {
            when {
		branch "dev-*"
            }
            steps {
                echo 'Hello, dev branch'
            }
        }

        stage('beta') {
            when {
		branch "beta-*"
            }
            steps {
                echo 'Hello, beta branch'
            }
        }

        stage('production') {
            when {
		branch "main-*"
            }
            steps {
                echo 'Hello, prod branch'
            }
        }
    }
}
