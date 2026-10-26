<?php require_once __DIR__ . '/../../utilitario/vista_segura.php'; require_once __DIR__ . '/../../utilitario/tema.php';
$escudo = htmlspecialchars('../' . (tema_actual()['logo'] ?: 'img/logo.jpeg'), ENT_QUOTES, 'UTF-8'); ?>
<script src="../js/apariencia.js?rev=<?php echo time(); ?>"></script>
<style>
  .ap-presets { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
  .ap-preset { display: flex; align-items: center; gap: 10px; border: 2px solid #E6E3DE; border-radius: 10px; padding: 8px 10px; background: #fff; cursor: pointer; text-align: left; font-size: .86rem; font-weight: 600; }
  .ap-preset:hover { border-color: var(--tema-primario-borde); }
  .ap-preset.activo { border-color: var(--tema-primario); box-shadow: 0 0 0 3px var(--tema-primario-claro); }
  .ap-chips { display: flex; flex: none; }
  .ap-chips i { width: 22px; height: 22px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.12); }
  .ap-chips i + i { margin-left: -7px; }
  .ap-color { display: flex; align-items: center; gap: 10px; }
  .ap-color input[type=color] { width: 54px; height: 42px; padding: 2px; border: 1px solid #ced4da; border-radius: 8px; background: #fff; cursor: pointer; }
  .ap-color input[type=text] { max-width: 120px; font-family: Consolas, monospace; text-transform: uppercase; }
  .ap-estilos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
  .ap-estilo { border: 2px solid #E6E3DE; border-radius: 10px; padding: 8px; cursor: pointer; background: #fff; text-align: center; font-size: .85rem; font-weight: 600; }
  .ap-estilo.activo { border-color: var(--tema-primario); box-shadow: 0 0 0 3px var(--tema-primario-claro); }
  .ap-estilo .ap-mini { display: flex; height: 54px; border-radius: 6px; overflow: hidden; margin-bottom: 6px; border: 1px solid #E6E3DE; }
  .ap-estilo .ap-mini b { width: 34%; display: block; }
  .ap-estilo .ap-mini span { flex: 1; background: #F6F5F2; }
  .ap-logo { display: flex; align-items: center; gap: 14px; }
  .ap-logo img { width: 72px; height: 72px; object-fit: contain; border: 1px solid #E6E3DE; border-radius: 10px; padding: 4px; background: #fff; }
  .ap-muestra { border: 1px solid #E6E3DE; border-radius: 12px; overflow: hidden; background: var(--tema-fondo); }
  .ap-muestra .ap-m-cab { background: var(--tema-primario); color: var(--tema-primario-texto); padding: 12px 16px; border-bottom: 4px solid var(--tema-acento); display: flex; align-items: center; gap: 10px; }
  .ap-muestra .ap-m-cab img { width: 38px; height: 38px; object-fit: contain; background: #fff; border-radius: 7px; padding: 2px; }
  .ap-muestra .ap-m-cuerpo { padding: 14px 16px; }
  .ap-muestra table { width: 100%; font-size: .85rem; background: #fff; border-radius: 8px; overflow: hidden; }
  .ap-muestra th { background: var(--tema-primario); color: var(--tema-primario-texto); padding: 6px 8px; border-bottom: 2px solid var(--tema-acento); }
  .ap-muestra td { padding: 6px 8px; border-bottom: 1px solid #F0EEEA; }
  .ap-etiqueta { background: var(--tema-acento); color: var(--tema-acento-texto); border-radius: 999px; padding: 1px 9px; font-size: .75rem; font-weight: 700; }
</style>

<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-8"><h1 class="m-0"><b>APARIENCIA DEL SISTEMA</b></h1></div>
      <div class="col-sm-4">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="../index.php">MENU</a></li>
          <li class="breadcrumb-item active">APARIENCIA</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<div class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-7">
        <div class="card card-primary card-outline">
          <div class="card-body">
            <p class="text-muted">Los colores y el escudo identifican al colegio en todas las pantallas y en el ingreso al sistema. Al elegir, el sistema se muestra con los nuevos colores para que vea cómo queda; se aplica para todos al pulsar <b>Guardar</b>.</p>

            <h5 class="mb-2"><b>Combinaciones sugeridas</b></h5>
            <div class="ap-presets mb-4" id="ap_presets"></div>

            <div class="row">
              <div class="col-md-6 form-group">
                <label for="ap_primario">Color principal <small class="text-muted">(menú, encabezados, botones)</small></label>
                <div class="ap-color"><input type="color" id="ap_primario_sel" aria-label="Elegir color principal"><input type="text" id="ap_primario" class="form-control" maxlength="7"></div>
              </div>
              <div class="col-md-6 form-group">
                <label for="ap_acento">Color de acento <small class="text-muted">(detalles, opción activa)</small></label>
                <div class="ap-color"><input type="color" id="ap_acento_sel" aria-label="Elegir color de acento"><input type="text" id="ap_acento" class="form-control" maxlength="7"></div>
              </div>
            </div>

            <label class="mt-2">Menú lateral</label>
            <div class="ap-estilos mb-4" id="ap_estilos">
              <button type="button" class="ap-estilo" data-estilo="COLOR"><div class="ap-mini"><b style="background:var(--tema-primario-oscuro)"></b><span></span></div>Del color principal</button>
              <button type="button" class="ap-estilo" data-estilo="OSCURO"><div class="ap-mini"><b style="background:#1F2226"></b><span></span></div>Oscuro</button>
              <button type="button" class="ap-estilo" data-estilo="CLARO"><div class="ap-mini"><b style="background:#fff;border-right:1px solid #E6E3DE"></b><span></span></div>Claro</button>
            </div>

            <div class="form-group">
              <label for="ap_lema">Lema o frase del colegio <small class="text-muted">(opcional; aparece bajo el nombre)</small></label>
              <input type="text" id="ap_lema" class="form-control" maxlength="150" placeholder="Ej.: Educación con valores para la vida">
            </div>

            <label>Escudo o logo</label>
            <div class="ap-logo mb-3">
              <img id="ap_logo" src="<?php echo $escudo; ?>" alt="Escudo actual">
              <div>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="Apariencia.cambiarEscudo()"><i class="fas fa-image"></i> Cambiar escudo</button>
                <div class="small text-muted mt-1">Imagen cuadrada (PNG o JPG). El nombre y los demás datos se editan en <i>Datos de la institución</i>, en el inicio.</div>
              </div>
            </div>

            <div id="ap_aviso"></div>
            <div class="d-flex flex-wrap" style="gap:8px">
              <button type="button" class="btn btn-primary" onclick="Apariencia.guardar()"><i class="fas fa-save"></i> Guardar</button>
              <button type="button" class="btn btn-outline-secondary" onclick="Apariencia.descartar()"><i class="fas fa-undo"></i> Descartar cambios</button>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-3"><b>Así se verá</b></h5>
            <div class="ap-muestra">
              <div class="ap-m-cab"><img id="ap_m_logo" src="<?php echo $escudo; ?>" alt=""><div><b id="ap_m_nombre"></b><div style="font-size:.8rem;opacity:.85" id="ap_m_lema"></div></div></div>
              <div class="ap-m-cuerpo">
                <table>
                  <thead><tr><th>Estudiante</th><th>Grado</th><th>Estado</th></tr></thead>
                  <tbody>
                    <tr><td>Ana Quispe Rojas</td><td>3.° Primaria</td><td><span class="ap-etiqueta">Matriculada</span></td></tr>
                    <tr><td>Luis Mamani Torres</td><td>3.° Primaria</td><td><span class="ap-etiqueta">Matriculado</span></td></tr>
                  </tbody>
                </table>
                <div class="mt-3 d-flex" style="gap:8px">
                  <button type="button" class="btn btn-primary btn-sm">Guardar</button>
                  <button type="button" class="btn btn-outline-primary btn-sm">Cancelar</button>
                </div>
              </div>
            </div>
            <p class="small text-muted mt-2 mb-0">Datos de ejemplo. El texto sobre cada color se elige solo (blanco u oscuro) para que siempre se lea bien.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  Apariencia.iniciar();
</script>
