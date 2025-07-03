# SIPRI Analyzer

![img.png](img.png)

[![License](https://img.shields.io/github/license/acardielf/sipri_analyzer)]

## Getting Started

1. If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
2. Run `docker compose build --pull --no-cache` to build fresh images
3. Run `docker compose up --wait` to set up and start a fresh Symfony project
4. Open `https://localhost` in your favorite web browser
   and [accept the auto-generated TLS certificate](https://stackoverflow.com/a/15076602/1352334)
5. Run `docker compose down --remove-orphans` to stop the Docker containers.

## Join the container

```bash
docker compose exec -it sipri bash
```

## Usage

### Install dependencies and first run

```bash
# Consider running this command inside the container:
bin/console composer install
bin/console doctrine:database:create --if-not-exists
bin/console doctrine:migrations:migrate --no-interaction
```

### Basic commands

#### To get required PDFs from the SIPRI website

```bash
bin/console sipri:get 1

#use --force to force download even if the file already exists
bin/console sipri:get --force 1
```

#### To extract positions from the PDFs

```bash
bin/console sipri:ext 1

# use --pagina to specify the page number to extract from
bin/console sipri:ext --pagina 8 1
```

#### To extract adjudications from the PDFs and attach to the offered positions

```bash
bin/console sipri:adj 1

# use --pagina to specify the page number to extract from
bin/console sipri:adj --pagina 8 1
```

#### Remove convocatorias and all related adjudications

```bash
bin/console sipri:del 1
```

#### Remove only adjudications

```bash
bin/console sipri:del --adj 1
```


#### Remove convocatorias and all related adjudications and files

```bash
bin/console sipri:del --full 1
```


#### Get, extract and process adjudications, in a loop

```bash
# use desired range {1..10} to get the affected convocatorias
for i in {1..10}; do 
    php bin/console:get "$i"; 
    php bin/console sipri:ex "$i"; 
    php bin/console sipri:adj "$i";
done
```

#### Generar static website

```bash
bin/console stenope:build --host=acardielf.github.io --base-url=/sipri_analyzer --scheme=https --no-sitemap ./docs
```   

### Debugging

If you need to debug the application, you can use the following command to start a Symfony server with Xdebug enabled:

```bash
XDEBUG_SESSION=1 PHP_IDE_CONFIG="serverName=symfony" php bin/console <your-command>
```

### Localizar convocatorias con orden desordenado

```bash
find . -type f -name "*_adjudicados.pdf" -exec pdftotext {} {}.txt \;
find . -type f -name "*_adjudicados.pdf.txt" -print0 | xargs -0 grep -L "ANEXO II" > ausentes.txt

# borrar los ficheros
find . -type f -name "*_adjudicados.pdf.txt" -exec rm {} \;
```

## Credits

Created by Ángel Cardiel, based on dunglas/symfony-docker template.
Created by [Kévin Dunglas](https://dunglas.dev), co-maintained by [Maxime Helias](https://twitter.com/maxhelias) and
sponsored by [Les-Tilleuls.coop](https://les-tilleuls.coop).

