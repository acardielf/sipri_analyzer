import tabula
from pathlib import Path
import sys

pdf_file = sys.argv[1]
csv_file = str(Path(pdf_file).with_suffix('.csv'))

tabula.convert_into(
    pdf_file,
    csv_file,
    output_format="csv",
    pages='all',
    relative_columns=True,
    columns=[
        26, # Apellidos, nombre y NIF
        50, # Puesto
        53, # Orden
        78, # Centro
        85, # Localidad
        90, # Provincia
        93, # Tipo Plaza
        99, # F.Prev.Cese
        103, # Obligatoriedad
    ],
    area=[
        151,
        26,
        538,
        805
    ],  # [top, left, bottom, right]
)
