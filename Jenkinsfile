pipeline {
    agent any
    environment {
        //sonarqube
        projectName = "Jarvis-project"
        scannerHome = tool 'sonarscanner'
        //nexus
        image_name = "stage.acoba.com/zaag-portfolio:latest"
    }
    stages {
        stage('checkout SCM Git') {
            steps {
                checkout scmGit(branches: [[name: '*/main']], extensions: [], userRemoteConfigs: [[url: 'https://github.com/aminos98/php-test']])
            }
        }
        stage('UNIT TEST') {
            steps {
                sh 'phpunit attendancemonitoring/tests/unitTest.php'
            }
        }
        stage('CODE ANALYSIS with SONARQUBE') {
            environment {
                PATH = "${scannerHome}/bin:${env.PATH}"
            }

            steps {
                withSonarQubeEnv('sonarserver') {
                    sh """
                        ${scannerHome}/bin/sonar-scanner \
                        -Dsonar.projectKey=${projectName} \
                        -Dsonar.sources=. \
                        -Dsonar.host.url=${env.SONAR_HOST_URL} \
                        -Dsonar.login=${env.SONAR_AUTH_TOKEN} \
                        -Dsonar.projectName=${projectName} \
                        -Dsonar.projectVersion=${env.BUILD_ID}
                    """
                }
            }
        }

        stage('Building Image') {
            steps {
                echo 'Building Image ...'
                sh "docker build -t ${image_name} ."
            }
        }
        
        stage('Pushing Image to Nexus') {
            steps {
                echo 'Pushing image to docker hosted repository on Nexus ...'
                
                withCredentials([usernamePassword(credentialsId: 'nexus', passwordVariable: 'PSW', usernameVariable: 'USER')]){
                    sh "echo ${PSW} | docker login -u ${USER} --password-stdin stage.acoba.com/repo"
                    sh "docker push ${image_name}"
                }
            }
        }
        stage('Deploy to AWS') {
            steps {
                withCredentials([[
                    $class: 'AmazonWebServicesCredentialsBinding',
                    accessKeyVariable: 'AWS_ACCESS_KEY_ID',
                    secretKeyVariable: 'AWS_SECRET_ACCESS_KEY',
                    credentialsId: 'Jenkins-With-Beanstalk-Credentials'
                ]]) {
                    //sh "aws configure set aws_access_key_id \$AWS_ACCESS_KEY_ID"
                    //sh "aws configure set aws_secret_access_key \$AWS_SECRET_ACCESS_KEY"
                    sh "echo 'n' | eb init --region eu-west-1 ws-jarvis-dev-docker"
                    sh "eb deploy"
                }
            }
        }
    }    
}
