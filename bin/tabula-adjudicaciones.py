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
    columns=[
        210, # Apellidos, nombre y NIF
        385, # Puesto
        415, # Orden
        605, # Centro
        665, # Localidad
        710, # Provincia
        738, # Tipo Plaza
        775, # F.Prev.Cese
        800, # Obligatoriedad
    ],
    area=[
        100,
        20,
        548,
        810,
    ],  # [top, left, bottom, right]
)
