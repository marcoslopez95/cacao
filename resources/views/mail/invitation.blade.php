<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Invitación a CACAO</title>
</head>
<body style="font-family:sans-serif;background:#F4F2EF;margin:0;padding:32px;">
<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:8px;padding:40px;">
    <h1 style="font-size:24px;color:#131110;margin:0 0 8px;">Te invitaron a CACAO</h1>
    <p style="color:#888780;margin:0 0 24px;">{{ $inviterName }} te invitó a acceder a la plataforma académica CACAO.</p>
    <a href="{{ $acceptUrl }}"
       style="display:inline-block;background:#C8521A;color:#fff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600;">
        Aceptar invitación
    </a>
    <p style="color:#888780;font-size:13px;margin:24px 0 0;">
        Este enlace expira el {{ $expiresAt }}.<br>
        Si no esperabas esta invitación, podés ignorar este correo.
    </p>
</div>
</body>
</html>
