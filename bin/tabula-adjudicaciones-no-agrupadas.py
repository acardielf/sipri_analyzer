import tabula
from pathlib import Path
import sys

pdf_file = sys.argv[1]
csv_file = str(Path(pdf_file).with_suffix('.json'))

# To get column limits and area coordinates, you can use the following command:
# pdftoppm 226_adjudicados.pdf pagina -png -f 1 -singlefile -r 72
# and then open the resulting image in an image editor to find the coordinates (p.e: GIMP)


tabula.convert_into(
    pdf_file,
    csv_file,
    output_format="json",
    pages='all',
    relative_columns=False,
    silent=True,
    columns=[
        200, # Apellidos, nombre y NIF
        242, # Orden
        426, # Centro
        489, # Localidad
        534, # Provincia
        693, # Puesto
        722, # Tipo Plaza
        763, # F.Prev.Cese
        789, # Obligatoriedad
    ],
    area=[
        100,
        20,
        548,
        810,
    ],  # [top, left, bottom, right]
)
