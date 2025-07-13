import tabula
from pathlib import Path
import sys

pdf_file = sys.argv[1]
csv_file = str(Path(pdf_file).with_suffix('.json'))

# To get column limits and area coordinates, you can use the following command:
# pdftoppm 226_plazas.pdf pagina -png -f 1 -singlefile -r 72
# and then open the resulting image in an image editor to find the coordinates (p.e: GIMP)


tabula.convert_into(
    pdf_file,
    csv_file,
    output_format="json",
    pages='all',
    relative_columns=False,
    columns=[
        196, # Centro
        286, # Localidad
        338, # Provincia
        570, # Puesto
        620, # Tipo Plaza
        670, # Numero Plazas
        720, # Obligatoriedad
        785, # F.Prev.Cese
    ],
    area=[
        100,
        20,
        548,
        810,
    ],  # [top, left, bottom, right]
)
